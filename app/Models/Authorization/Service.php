<?php

namespace App\Models\Auth;

// use Razorpay\OAuth;

class Service
{
    public function __construct()
    {
        $this->oauthServer = new OAuth\OAuthServer();
    }

    public funtion getAuthCode(array $input)
    {
        // TODO: Validate input
        try
        {
            $this->oauthServer = new OAuth\OAuthServer();

            return $oauthServer->getAuthCode($input);
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
            $this->oauthServer = new OAuth\OAuthServer();

            return $oauthServer->getAccessToken($input);
        }
        catch (\Exception $ex)
        {
            //TODO: Add tracing
        }
    }
}