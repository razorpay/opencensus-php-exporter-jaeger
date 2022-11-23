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
        $client_ids = getenv('CLIENT_MIGRATE_IDS');

        if (!empty($client_ids)) {
            $clients = explode(',', $client_ids);
            foreach (Client::with(['application' => function ($query) {
                $query->withTrashed();
                }])->whereIn('id', $clients)->withTrashed()->get() as $client)
            {
                $count++;
                Trace::info(TraceCode::MIGRATE_CLIENT_REQUEST, array('count' => $count, 'client_id' => $client->getId(), 'action' => $action));
                DB::transaction(function () use ($client, $core, $action) {
                    $this->outboxSend($core, $client, $action);
                });
            }
        }

        return null;
    }

    /**
     * @param Core $core
     * @param mixed $client
     * @return void
     */
    function outboxSend(Core $core, mixed $client): void {
        // if the application is not present then ignore
        if ($client->application === null) {
            Trace::info(TraceCode::OUTBOX_INVALID_CLIENT, array('client_id' => $client->getId()));
            return;
        }

        if ($this->enable_cassandra_outbox)
        {
            app("outbox")->send("create_client", $core->getOutboxPayload($client, $client->application));
        }
        if ($this->enable_postgres_outbox)
        {
            app("outbox")->send("create_client_postgres", $core->getOutboxPayload($client, $client->application));
        }
    }
}
