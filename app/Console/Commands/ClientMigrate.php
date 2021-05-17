<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Razorpay\OAuth\Client\Core;
use Razorpay\OAuth\Client\Entity as Client;

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Read all existing Client entities and create outbox entries for all of them.
     * This will only process the non-revoked clients.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::transaction(function () {
            $core = new Core;
            foreach (Client::all() as $client) {
                app("outbox")->send("create_client", $core->getOutboxPayload($client, $client->application));
            }
        });

        return null;
    }
}
