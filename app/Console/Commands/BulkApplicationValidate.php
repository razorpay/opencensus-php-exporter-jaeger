<?php

namespace App\Console\Commands;

use App\Constants\TraceCode;
use App\Services\EdgeService;
use Illuminate\Console\Command;
use Razorpay\OAuth\Application\Service;
use Razorpay\Trace\Facades\Trace;

class BulkApplicationValidate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bulk_application:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate Oauth Client Sync with edge';


    private string $applicationType;
    private string $inputFileName;
    private Service $applicationService;
    private EdgeService $edgeCassandra;
    private EdgeService $edgePostgres;
    private bool $enable_cassandra_outbox;
    private bool $enable_postgres_outbox;
    private int $edgeCallWaitTimeInMs;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->inputFileName = env("BULK_APPLICATION_CREATE_FILE_NAME", '');
        $this->enable_cassandra_outbox = env("ENABLE_CASSANDRA_OUTBOX", true);
        $this->enable_postgres_outbox = env("ENABLE_POSTGRES_OUTBOX", true);
        $this->applicationType = env("BULK_APPLICATION_CREATE_APP_TYPE", '');
        $this->applicationService = new Service();
        if ($this->enable_cassandra_outbox){
            $this->edgeCassandra = new EdgeService([], env('EDGE_URL'), env('EDGE_SECRET'));
        }
        if ($this->enable_postgres_outbox){
            $this->edgePostgres = new EdgeService([], env('EDGE_POSTGRES_URL'), env('EDGE_POSTGRES_SECRET'));
        }
        $this->edgeCallWaitTimeInMs = intval(env("EDGE_CALL_WAIT_MS", 1000));
    }

    /**
     * Read all merchant IDs present in the file
     * and validate if all clientids of merchant are synced to edge or not.
     *
     * @return mixed
     */
    public function handle(): mixed
    {
        if ($this->inputFileName == "" || $this->applicationType == "") {
            throw new \Exception("mandatory params missing");
        }

        $fileName = __DIR__ . "/../../../data/" . $this->inputFileName;
        try {
            $handle = fopen($fileName, "r");
            while (($line = fgets($handle)) !== false) {
                $merchantId = trim($line);
                try {
                    // Check if active application(s) already exist for this MID
                    $fetchResponse = $this->applicationService->fetchMultiple(array(
                        "merchant_id" => $merchantId,
                        "type" => $this->applicationType
                    ));
                } catch (\Exception $ex) {
                    Trace::error(TraceCode::VALIDATE_CLIENT_EDGE_SYNC, [
                        "merchant_id" => $merchantId,
                        "message" => "Could not fetch Applications from authdb",
                        "exception" => $ex->getMessage()
                    ]);

                    continue;
                }

                $numApplications = $fetchResponse["count"];

                if ($numApplications <= 0) {
                    Trace::info(TraceCode::VALIDATE_CLIENT_EDGE_SYNC, [
                        "client_id" => $merchantId,
                        "message" => "zero client at auth service"
                    ]);
                    continue;
                }

                try {

                    foreach ($fetchResponse["items"] as $item) {
                        foreach ($item["client_details"] as $client) {
                            $clientId = $client["id"];
                            //we need to check in both cassandra and postgres
                            if ($this->enable_cassandra_outbox) {
                                $this->edgeCassandra->getOauth2Client($clientId);
                            }
                            if ($this->enable_postgres_outbox) {
                                $this->edgePostgres->getOauth2Client($clientId);
                            }
                            //rate limiting edge requests
                            usleep($this->edgeCallWaitTimeInMs * 1000);
                            Trace::info(TraceCode::VALIDATE_CLIENT_EDGE_SYNC, [
                                "client_id" => $clientId,
                                "message" => "client_id synced to edge"
                            ]);
                        }
                    }

                    Trace::info(TraceCode::VALIDATE_CLIENT_EDGE_SYNC, [
                        "merchant_id" => $merchantId,
                        "message" => "All client synced to edge"
                    ]);

                } catch (\Exception $ex) {
                    Trace::error(TraceCode::VALIDATE_CLIENT_EDGE_SYNC, [
                        "merchant_id" => $merchantId,
                        "message" => "Could not check client on edge",
                        "exception" => $ex->getMessage()]);

                    continue;
                }
            }
            fclose($handle);
        } catch (\Exception $ex) {
            Trace::error(TraceCode::VALIDATE_CLIENT_EDGE_SYNC, [
                "message" => "unknown error occured",
                "exception" => $ex->getMessage()]);
        }

        return null;
    }
}
