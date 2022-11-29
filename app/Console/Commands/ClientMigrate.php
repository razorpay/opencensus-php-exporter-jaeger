<?php

namespace App\Console\Commands;

use App\Constants\TraceCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Razorpay\OAuth\Client\Core;
use Razorpay\OAuth\Client\Entity as Client;
use Razorpay\Trace\Facades\Trace;

class ClientMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'client:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate OAuth clients to Kong';

    /**
     * @var $enable_cassandra_outbox
     */
    protected $enable_cassandra_outbox;

    /**
     * @var $enable_postgres_outbox
     */
    protected $enable_postgres_outbox;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->enable_cassandra_outbox = env("ENABLE_CASSANDRA_OUTBOX", true);

        $this->enable_postgres_outbox = env("ENABLE_POSTGRES_OUTBOX", false);
    }

    /**
     * Read all existing Client entities and create outbox entries for all of them.
     * This will only process the non-revoked clients.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = 0;
        $core  = new Core;

        $action = getenv('CLIENT_MIGRATE_ACTION');
        $action = empty($action) ? "create" : $action;
        $client_ids = getenv('CLIENT_MIGRATE_IDS');
        $chunk_size = getenv('CLIENT_MIGRATE_CHUNK_SIZE');
        $chunk_size = empty($chunk_size) ? 50000 : (int) $chunk_size;

        if (!empty($client_ids)) {
            $clients = explode(',', $client_ids);
            foreach (Client::with(['application' => function ($query) {
                $query->withTrashed();
                }])->whereIn('id', $clients)->withTrashed()->get() as $client)
            {
                $count++;
                Trace::info(TraceCode::MIGRATE_CLIENT_REQUEST, array('count' => $count, 'client_id' => $client->getId(), 'action' => $action));
                $payload = [];
                if ($action == 'create') {
                    $payload = $core->getOutboxPayload($client, $client->application);
                } elseif ($action == 'delete') {
                    $payload['id'] = $client->getId();
                    $payload['application_type'] = $client->application->getType();
                }
                DB::transaction(function () use ($action, $payload) {
                    $this->outboxSend($action, $payload);
                });
            }
        } else {
            // Full migration
            Client::orderBy('id')->chunk($chunk_size, function ($clients) use($core, $action, &$count) {
                foreach ($clients as $client) {
                    $count++;
                    Trace::info(TraceCode::MIGRATE_CLIENT_REQUEST, array('count' => $count, 'client_id' => $client->getId(), 'action' => $action));
                    $payload = $core->getOutboxPayload($client, $client->application);
                    DB::transaction(function () use ($action, $payload) {
                        $this->outboxSend($action, $payload);
                    });
                }
            });
        }

        return null;
    }

    /**
     * @param string $action
     * @param array $payload
     * @return void
     */
    function outboxSend(string $action, array $payload): void {
        if ($this->enable_cassandra_outbox) {
            app("outbox")->send($action . "_client", $payload);
        }
        if ($this->enable_postgres_outbox) {
            app("outbox")->send($action . "_client_postgres", $payload);
        }
    }
}
