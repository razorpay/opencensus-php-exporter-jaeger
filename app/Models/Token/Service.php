<?php

namespace App\Models\Token;

use Razorpay\OAuth\Client;
use Razorpay\OAuth\Token\Entity;

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

        try
        {
            $client = (new Client\Repository)->getClientEntity(
                $input[Entity::CLIENT_ID],
                "",
                $input['client_secret'],
                true
            );
        }
        catch (\Exception $ex)
        {
            throw $ex;
        }
    }
}
