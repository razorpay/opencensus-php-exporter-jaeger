<?php

namespace App\Models\Token;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\RefreshToken;

class Service
{
    protected $service;

    protected $OAuthTokenService;

    protected $OAuthRefreshTokenService;

    public function __construct()
    {
        $this->validator = new Validator;
        $this->OAuthTokenService         = new Token\Service;
        $this->OAuthRefreshTokenService  = new RefreshToken\Service;
    }

    /**
     * Validate input request and validate refresh token and access token and revokes them
     * Calls OAuth Token service according to the token_type_hint passed
     *
     * @param array  $input
     *
     *
     */
    public function validateRevokeTokenRequest($input)
    {
        $this->validator->validateInput('revoke_by_partner', $input);

        if($input['token_type_hint'] === 'access_token')
        {
            $this->OAuthTokenService->revokeAccessToken($input);
        }

        if($input['token_type_hint'] === 'refresh_token')
        {
            $this->OAuthRefreshTokenService->revokeRefreshToken($input);
        }
    }
}
