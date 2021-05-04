<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Razorpay\OAuth\Application\Entity as Application;
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
            foreach (Client::all() as $client) {
                app("outbox")->send("create_client", $this->getOutboxPayload($client));
            }
        });

        return null;
    }

    private function getOutboxPayload(Client $client)
    {
        $payload[Client::SECRET]         = $client->getSecret();
        $payload[Client::ID]             = $client->getId();
        $payload[Client::REDIRECT_URL]   = $client->getRedirectUrl();
        $payload[Client::ENVIRONMENT]    = $client->getEnvironment();
        $payload[Client::MERCHANT_ID]    = $client->getMerchantId();
        $payload[Client::APPLICATION_ID] = $client->getApplicationId();
        $payload[Application::NAME]      = $client->getName();
        return $payload;
    }
}
