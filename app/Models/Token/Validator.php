<?php

namespace App\Models\Token;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    protected static $revokeByPartnerRules = [
        'client_id'       => 'required|string',
        'client_secret'   => 'required|string',
        'token'           => 'required|string',
        'token_type_hint' => 'required|string',
    ];
}
