<?php

namespace App\Models\Token;

use App\Error\ErrorCode;
use App\Exception\BadRequestException;
use App\Models\Auth;
use Razorpay\OAuth;
use Razorpay\OAuth\Token as OauthToken;
use Razorpay\OAuth\Client;

class Service
{
    protected $service;

    public function __construct()
    {
        $this->service = new OauthToken\Service;

        $this->validator = new Validator;

        $this->oauthServer = new OAuth\OAuthServer(env('APP_ENV'), new Auth\Repository);
    }


}
