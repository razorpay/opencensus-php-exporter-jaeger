<?php

namespace App\Console\Commands;
use App\Constants\TraceCode;
use Illuminate\Console\Command;
use Razorpay\OAuth\Application\Service;
use Razorpay\Trace\Facades\Trace;

class BulkApplicationCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bulk_application:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create OAuth Applications for a list of Merchant IDs';

    private string $applicationName;
    private string $applicationType;
    private string $applicationWebsite;
    private string $inputFileName;
    private bool $useSeparateOutboxJob;
    private Service $applicationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->applicationName = getenv("BULK_APPLICATION_CREATE_APP_NAME");
        $this->applicationType = getenv("BULK_APPLICATION_CREATE_APP_TYPE");
        $this->applicationWebsite = getenv("BULK_APPLICATION_CREATE_APP_WEBSITE");
        $this->inputFileName = getenv("BULK_APPLICATION_CREATE_FILE_NAME");
        $this->useSeparateOutboxJob = getenv("BULK_APPLICATION_CREATE_USE_SEPARATE_OUTBOX_JOB") === "true";

        $this->applicationService = new Service();
    }

    /**
     * Read all merchant IDs present in the file
     * and create OAuth Applications for each of them, if they don't exist.
     *
     * @return mixed
     */
    public function handle(): mixed
    {
        $fileName = __DIR__ . "/../../../data/" . $this->inputFileName;
        try
        {
            $handle = fopen($fileName, "r");
            while (($line = fgets($handle)) !== false)
            {
                $merchantId = trim($line);
                try
                {
                    // Check if active application(s) already exist for this MID
                    $fetchResponse = $this->applicationService->fetchMultiple(array(
                        "merchant_id" => $merchantId,
                        "type" => $this->applicationType
                    ));
                }
                catch (\Exception $ex)
                {
                    Trace::info(TraceCode::ERROR_EXCEPTION, [
                        "merchant_id" => $merchantId,
                        "message" => "Could not fetch Application",
                        "exception" => $ex->getMessage()]);

                    continue;
                }

                $numApplications = $fetchResponse["count"];

                Trace::info(TraceCode::GET_APPLICATION_REQUEST, [
                    "merchant_id" => $merchantId,
                    "message" => $numApplications . " applications found"]);

                if ($numApplications > 0)
                    continue;

                try
                {
                    // If no applications are found, create one
                    $this->applicationService->create(array(
                        "merchant_id" => $merchantId,
                        "name" => $this->applicationName,
                        "website" => $this->applicationWebsite,
                        "type" => $this->applicationType
                    ),
                    $this->useSeparateOutboxJob);
                }
                catch (\Exception $ex)
                {
                    Trace::info(TraceCode::ERROR_EXCEPTION, [
                        "merchant_id" => $merchantId,
                        "message" => "Could not create Application",
                        "exception" => $ex->getMessage()]);

                    continue;
                }

                Trace::info(TraceCode::CREATE_APPLICATION_REQUEST, [
                    "merchant_id" => $merchantId,
                    "message" => "Application created"]);
            }
            fclose($handle);
        }
        catch (\Exception $ex)
        {
            Trace::info(TraceCode::ERROR_EXCEPTION, [
                "message" => "Could not open file " . $fileName,
                "exception" => $ex->getMessage()]);
        }

        return null;
    }
}
