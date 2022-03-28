<?php

namespace App\Models\Token;

use App\Models\Auth;
use Razorpay\OAuth;
use Razorpay\OAuth\Token as OauthToken;

class Service
{
    protected $service;

    public function __construct()
    {
        $this->validator = new Validator;
    }

    public function validateRevokeTokenRequest($input)
    {
        $this->validator->validateInput('revoke_by_partner', $input);
    }
}
