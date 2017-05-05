<?php

namespace App\Models\Auth;

use Razorpay\OAuth;

class Service
{
    public function __construct()
    {
        $this->oauthServer = new OAuth\OAuthServer();
    }

    public function postAuthCode(array $input)
    {
        // TODO: Validate input
        try
        {
            return $this->oauthServer->getAuthCode($input);
        }
        catch (\Exception $ex)
        {
            //TODO: Add tracing
        }
    }

    public function getAccessToken(array $input)
    {
        // TODO: Validate input
        try
        {
            return $this->oauthServer->getAccessToken($input);
        }
        catch (\Exception $ex)
        {
            //TODO: Add tracing
        }
    }
}