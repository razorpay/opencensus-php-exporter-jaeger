<?php

namespace App\Models\Token;


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
