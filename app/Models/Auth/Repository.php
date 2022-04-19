<?php

namespace App\Models\Auth;

use Illuminate\Support\Facades\DB;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Token\Type;
use Razorpay\Trace\Logger as Trace;

use App\Constants\TraceCode;

class Repository extends Token\Repository
{
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
                app("outbox")->sendWithDelay("check_token_consistency",
                                             ["public_id" => $accessTokenEntity->getPublicTokenWithPrefix(),
                                              "jti" => $accessTokenEntity->getIdentifier()],
                                             600);
            });

            // We need to create oauth public_token in edge inside `identifier` table so that it can be validated at edge.
            app("edge")->postPublicIdToEdge($accessTokenEntity->getPublicTokenWithPrefix(), $accessTokenEntity->getMerchantId(),
                                            $accessTokenTTLInSeconds, $accessTokenEntity->getMode(), $accessTokenEntity->getIdentifier());
        }
        catch (\Throwable $ex)
        {
            // TODO: This is added temporarily.
            // Will remove it after testing for a while in prod.
            app("trace")->traceException($ex, Trace::CRITICAL, TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_FAILED);
        }

        $this->saveOrFail($accessTokenEntity);
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
}
