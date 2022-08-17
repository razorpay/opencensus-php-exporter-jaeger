<?php

namespace App\Models\Token;

use App\Constants\RequestParams;
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
     * @param array  $input
     *
     *
     */
    public function handleRevokeTokenRequest($input)
    {
        $this->validator->validateInput('revoke_by_partner', $input);

        if($input[RequestParams::TOKEN_TYPE_HINT] === 'access_token')
        {
            $this->oauthTokenService->revokeAccessToken($input);
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
     */
    public function handleRevokeTokenRequestForMobileApp($id, $input)
    {
        $this->validator->validateInput(Constant::REVOKE_FOR_MOBILE_APP, $input);

        $this->oauthTokenService->revokeToken($id, $input);
    }
}
