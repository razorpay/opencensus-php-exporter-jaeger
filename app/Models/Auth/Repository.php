<?php

namespace App\Models\Auth;

use Illuminate\Support\Facades\DB;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

use OpenCensus\Trace\Tracer;
use Razorpay\OAuth\Token;
use Razorpay\OAuth\Token\Type;
use Razorpay\Trace\Logger as Trace;

use App\Constants\TraceCode;

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

    public function __construct() {
        parent::__construct();

        $this->enable_cassandra_outbox = env("ENABLE_CASSANDRA_OUTBOX", true);

        $this->enable_postgres_outbox = env("ENABLE_POSTGRES_OUTBOX", false);
    }

    public function getEntityClass()
    {
        return "Razorpay\\OAuth\\Token\\Entity";
    }

    /**
     * Persists a new access token to permanent storage.
     *
     * @param AccessTokenEntityInterface $accessTokenEntity
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $accessTokenTTLInSeconds = $accessTokenEntity->getExpiryDateTime()->getTimestamp() - (new \DateTime('now'))->getTimestamp();

        try
        {
            // send outbox event here.
            // This will check if public token exist in edge and in auth service after 1 hour
            // If it is not present in auth service but present in edge, then it will delete from edge.
            DB::transaction(function () use ($accessTokenEntity) {
                $this->outboxSend($accessTokenEntity);
            });

            // We need to create oauth public_token in edge inside `identifier` table so that it can be validated at edge.
            $payload = [Constant::PUBLIC_TOKEN => $accessTokenEntity->getPublicTokenWithPrefix(),
                        Constant::IDENTIFIER   => $accessTokenEntity->getIdentifier(),
                        Constant::MID          => $accessTokenEntity->getMerchantId(),
                        Constant::MODE         => $accessTokenEntity->getMode(),
                        Constant::TTL          => $accessTokenTTLInSeconds,
                        Constant::USER_ID      => $accessTokenEntity->getUserId()
            ];
            if ($this->enable_cassandra_outbox)
            {
                try
                {
                    app("edge")->postPublicIdToEdge($payload);
                }
                catch (\Throwable $ex)
                {
                    app("trace")->traceException($ex, Trace::CRITICAL, TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_CASSANDRA_FAILED);
                    throw $ex;
                }
            }
            if ($this->enable_postgres_outbox)
            {
                try
                {
                    app("edge_postgres")->postPublicIdToEdge($payload);
                }
                catch (\Throwable $ex)
                {
                    app("trace")->traceException($ex, Trace::CRITICAL, TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_POSTGRES_FAILED);
                    throw $ex;
                }
            }
        }
        catch (\Throwable $ex)
        {
            // This is a temporary fix to always return error if postPublicIdToEdge fails
            // Long term fix would be to fail request if post to Edge fails for certain grant types only where strong consistency is required. For other grant types, don't return an error and use eventual consistency.
            app("trace")->traceException($ex, Trace::CRITICAL, TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_FAILED);
            throw $ex;
        }

        Tracer::inSpan(['name' => 'persistNewAccessToken.saveOrFail'],
            function () use($accessTokenEntity) {
                $this->saveOrFail($accessTokenEntity);
            });
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
                600);
        }
        if ($this->enable_postgres_outbox)
        {
            app("outbox")->sendWithDelay("check_token_consistency_postgres",
                ["public_id" => $accessTokenEntity->getPublicTokenWithPrefix()],
                600);
        }
    }
}
