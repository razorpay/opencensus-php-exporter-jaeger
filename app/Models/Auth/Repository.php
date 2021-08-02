<?php

namespace App\Models\Auth;

use Razorpay\OAuth\Token;
use Razorpay\Trace\Logger as Trace;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;

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

        // TODO:
        // send outbox event here.


        try
        {
            // We need to create oauth public_token in edge inside `identifier` table so that it can be validated at edge.
            app("edge")->postPublicIdToEdge($accessTokenEntity->getPublicTokenWithPrefix(), $accessTokenEntity->getMerchantId(), $accessTokenTTLInSeconds, $accessTokenEntity->getMode());
        }
        catch (\Throwable $ex)
        {
            // TODO: This is added temporarily.
            // Will remove it after testing for a while in prod.
            app("trace")->traceException($ex, Trace::CRITICAL, TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_FAILED);
        }

        $this->saveOrFail($accessTokenEntity);
    }
}
