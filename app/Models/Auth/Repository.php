<?php

namespace App\Models\Auth;

use Illuminate\Support\Facades\DB;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

use OpenCensus\Trace\Tracer;
use Razorpay\OAuth\Token;
use Razorpay\OAuth\Token\Type;

class Repository extends Token\Repository
{
    /**
     * @var $enable_cassandra_outbox
     */
    protected $enable_cassandra_outbox;

    /**
     * @var $enable_postgres_outbox
     */
    protected $enable_postgres_outbox;

    public  function __construct() {
        parent::__construct();

        $this->enable_cassandra_outbox = env("ENABLE_CASSANDRA_OUTBOX", true);

        $this->enable_postgres_outbox = env("ENABLE_POSTGRES_OUTBOX", false);
    }

    public function getEntityClass()
    {
        return "Razorpay\\OAuth\\Token\\Entity";
    }

    /**
     * Makes sync calls
     * to Edge to sync the public token and,
     * to Signer Redis to sync public OAuth credentials.
     * Also, creates delayed check_token_consistency_jobs for each of the sync calls
     * DOES NOT persist the token in DB.
     * @param AccessTokenEntityInterface $accessTokenEntity
     * @throws \Throwable
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        // TODO: Improve Code Design. Details here: https://razorpay.atlassian.net/browse/EAX-500
        // The TTL calculated below will always be positive since the expiry date time of an access token is derived by adding now and TTL during token generation, where TTL is determined from the grant type.
        // All grant types will have a TTL that is big enough to make sure the ttl calculated below will never be negative.
        $ttlInSeconds = $accessTokenEntity->getExpiryDateTime()->getTimestamp() - (new \DateTime('now'))->getTimestamp();
        $this->postPublicIdToEdge($accessTokenEntity, $ttlInSeconds);

        // If Edge request was successful, create public OAuth credentials in signer redis
        // Signer Cache needs to store <public token, client_secret> mapping
        // to support signing of payloads using Public OAuth credentials

        // Although we could have written to signer cache before writing to Edge since these writes are independent
        // The reason we chose to write to signer cache after Edge is because writing to Edge is more prone to failures
        // because of timeouts and multiple HTTP requests involved as compared to writing to Signer Cache, which involves a single Redis write
        // This enables us to decrease the chances of creating futile check token consistency jobs for signer cache

        $this->postPublicOAuthCredentialsToSignerCache($accessTokenEntity, $ttlInSeconds);
        // If any of the sync calls (to Edge or Signer Cache) fail, we will return an error to the user
        // This is a short-term fix to handle sync failures.
        // Long term fix would be to avoid making sync calls for grant types where strong consistency is not required
        // And use an eventual consistency model for those cases
    }

    protected function postPublicIdToEdge(AccessTokenEntityInterface $accessTokenEntity, int $ttlInSeconds)
    {
        // send outbox event here.
        // This will check if public token exist in edge and in auth service after 10 minutes
        // If it is not present in auth service but present in edge, then it will delete from edge.
        DB::transaction(function () use ($accessTokenEntity) {
            $this->outboxSend($accessTokenEntity);
        });

        // We need to create oauth public_token in edge inside `identifier` table so that it can be validated at edge.
        $payload = [Constant::PUBLIC_TOKEN => $accessTokenEntity->getPublicTokenWithPrefix(),
            Constant::IDENTIFIER   => $accessTokenEntity->getIdentifier(),
            Constant::MID          => $accessTokenEntity->getMerchantId(),
            Constant::MODE         => $accessTokenEntity->getMode(),
            Constant::TTL          => $ttlInSeconds,
            Constant::USER_ID      => $accessTokenEntity->getUserId()
        ];
        if ($this->enable_cassandra_outbox)
        {
            app("edge")->postPublicIdToEdge($payload);
        }
        if ($this->enable_postgres_outbox)
        {
            app("edge_postgres")->postPublicIdToEdge($payload);
        }
    }

    protected function postPublicOAuthCredentialsToSignerCache(AccessTokenEntityInterface $accessTokenEntity, int $ttlInSeconds)
    {
        // Create token consistency job first that will check after 10 minutes if public token exists on both auth service and signer cache.
        // If token is not found in auth service, it will be deleted from signer cache
        DB::transaction(function () use ($accessTokenEntity) {
            app("outbox")->sendWithDelay(Constant::OUTBOX_PAYLOAD_SIGNER_CACHE_CHECK_TOKEN_CONSISTENCY,
                ["public_id" => $accessTokenEntity->getPublicTokenWithPrefix()],
                Constant::OUTBOX_CHECK_TOKEN_CONSISTENCY_JOB_DELAY_MS);
        });

        $rawSecret = $accessTokenEntity->getClient()->getSecret();
        app("signer_cache")->writeCredentials($accessTokenEntity->getPublicTokenWithPrefix(), $rawSecret, $ttlInSeconds);
    }

    public function findByPublicTokenIdAndMode(string $publicToken, string $mode)
    {
        return $this->newQuery()
            ->where(Token\Entity::TYPE, Type::ACCESS_TOKEN)
            ->where(Token\Entity::PUBLIC_TOKEN, $publicToken)
            ->where(Token\Entity::MODE, $mode)
            ->first();
    }

    public function findAllAccessTokens()
    {
        return $this->newQuery()
            ->where(Token\Entity::TYPE, Type::ACCESS_TOKEN)
            ->where(Token\Entity::EXPIRES_AT, '>' , 1629168659)
            ->get();
    }

    /**
     * @param AccessTokenEntityInterface $accessTokenEntity
     * @return void
     */
    function outboxSend(AccessTokenEntityInterface $accessTokenEntity): void {
        if ($this->enable_cassandra_outbox)
        {
            app("outbox")->sendWithDelay("check_token_consistency",
                ["public_id" => $accessTokenEntity->getPublicTokenWithPrefix()],
                Constant::OUTBOX_CHECK_TOKEN_CONSISTENCY_JOB_DELAY_MS);
        }
        if ($this->enable_postgres_outbox)
        {
            app("outbox")->sendWithDelay("check_token_consistency_postgres",
                ["public_id" => $accessTokenEntity->getPublicTokenWithPrefix()],
                Constant::OUTBOX_CHECK_TOKEN_CONSISTENCY_JOB_DELAY_MS);
        }
    }
}
