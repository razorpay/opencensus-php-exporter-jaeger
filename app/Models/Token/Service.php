<?php

namespace App\Models\Token;

use Trace;
use App\Exception\LogicException;
use App\Models\Auth;
use DateTimeImmutable;
use App\Constants\TraceCode;
use Razorpay\OAuth\OAuthServer;
use App\Constants\RequestParams;
use League\OAuth2\Server\Exception\OAuthServerException;
use Razorpay\OAuth\Exception\BadRequestException;
use Razorpay\OAuth\Token;
use Razorpay\OAuth\RefreshToken;

class Service
{
    protected $service;

    protected $oauthTokenService;

    protected $oauthRefreshTokenService;

    public function __construct()
    {
        $this->validator = new Validator;
        $this->oauthTokenService         = new Token\Service;
        $this->oauthRefreshTokenService  = new RefreshToken\Service;
    }

    /**
     * Validate input request and validate refresh token and access token and revokes them
     * Calls OAuth Token service according to the token_type_hint passed
     *
     * @param array $input
     *
     *
     * @throws BadRequestException
     * @throws OAuthServerException
     */
    public function handleRevokeTokenRequest($input)
    {
        $this->validator->validateInput('revoke_by_partner', $input);

        if($input[RequestParams::TOKEN_TYPE_HINT] === 'access_token')
        {
            $this->oauthTokenService->revokeAccessTokenAfterValidation($input);
        }

        if($input[RequestParams::TOKEN_TYPE_HINT] === 'refresh_token')
        {
            $this->oauthRefreshTokenService->revokeRefreshToken($input);
        }
    }

    /**
     * Validate input request. Post that revoke token for mobile app for merchant user pair
     * @param $id
     * @param $input
     * @return void
     * @throws \Exception
     */
    public function handleRevokeTokenRequestForMobileApp($id, $input)
    {
        $this->validator->validateInput(Constant::REVOKE_FOR_MOBILE_APP, $input);

        $this->oauthTokenService->revokeAccessToken($id, $input);
    }

    /**
     * @param string $appId
     * @param array $input
     *
     * @return false
     * @throws LogicException
     */
    public function revokeApplicationAccess(string $appId, array $input)
    {
        $this->validator->validateInput(Constant::REVOKE_OAUTH_APP_ACCESS, $input);

        $merchantId = $input['merchant_id'];

        $latestExpiredRefreshTokenTime = (new Token\Service())->latestExpiredRefreshTokenTime()->getTimestamp();

        // While fetching the list of access token Ids, we will filter out any access token which is older then 6 months,
        // since its respective refresh token would have expired.
        $allActiveTokens = (new Token\Repository)->fetchTokenIdsByMerchantAndApp($appId, $merchantId, $latestExpiredRefreshTokenTime);

        Trace::info(TraceCode::REVOKE_TOKENS_REQUEST,
            [
                'active_tokens'  => $allActiveTokens->getIds(),
                'tokens_count'   => count($allActiveTokens),
                'merchant_id'    => $merchantId,
                'application_id' => $appId
            ]
        );

        if (count($allActiveTokens) === 0)
        {
            throw new LogicException('This application doesn\'t have any access of the merchant');
        }

        $tokenRepo = new Token\Repository();

        $tokenRepo->beginTransaction();

        foreach ($allActiveTokens->getIds() as $accessTokenId)
        {
            try
            {
                $this->oauthTokenService->revokeAccessToken($accessTokenId, $input);
            }
            catch (\Throwable $e)
            {
                $tracePayload = [
                    'merchant_id'       => $merchantId,
                    'application_id'    => $appId,
                    'token_id'          => $accessTokenId,
                    'code'              => $e->getCode(),
                    'message'           => $e->getMessage(),
                ];

                Trace::critical(TraceCode::REVOKE_TOKEN_FAILED, $tracePayload);

                $tokenRepo->rollback();

                return false;
            }
        }

        $apiService = (new Auth\Service)->getApiService();

        // TODO: Replace this approach to an outbox approach
        $response = $apiService->revokeMerchantApplicationMapping($appId, $merchantId);

        if($response === false)
        {
            $tokenRepo->rollback();

            return false;
        }

        $tokenRepo->commit();

        return true;
    }
}
