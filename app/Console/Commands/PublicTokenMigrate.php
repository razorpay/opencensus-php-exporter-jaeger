<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use Razorpay\Trace\Facades\Trace;

use App\Constants\TraceCode;
use App\Models\Auth\Repository;


class PublicTokenMigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'public_token:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate OAuth public tokens to Kong as identifier';

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
     * Read all existing public token entities and create outbox entries for all of them.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = 0;
        $tokens = (new Repository)->findAllAccessTokens();
        foreach ($tokens as $token) {
            $count++;
            Trace::info(TraceCode::MIGRATE_PUBLIC_TOKEN_REQUEST, array('count' => $count, 'token_id' => $token->getPublicTokenWithPrefix()));
            DB::transaction(function () use ($token) {
                app("outbox")->send("create_public_token",
                    [
                        "merchant_id" => $token->getMerchantId(),
                        "public_id" => $token->getPublicTokenWithPrefix(),
                        "ttl" => $token->getExpiryDateTime()->getTimestamp() - (new \DateTime('now'))->getTimestamp(),
                        "mode" => $token->getMode(),
                        "jti" => $token->getIdentifier();
                        "user_id" => $token->getUserId();
                    ], null, false);
            });
        }

        return null;
    }
}
