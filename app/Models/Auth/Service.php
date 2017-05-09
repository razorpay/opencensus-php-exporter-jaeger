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
        $userData['email'] = array_pop($input);
        $userData['name'] = array_pop($input);
        $userData['id'] = array_pop($input);
        try
        {
            return $this->oauthServer->getAuthCode($input, $userData);
        }
        catch (\Exception $ex)
        {
            //TODO: Add tracing
            var_dump($ex->getMessage());
        }
    }

    public function getAccessToken(array $input)
    {
        // TODO: Validate input
        return $this->oauthServer->getAccessToken($input);
    }
}