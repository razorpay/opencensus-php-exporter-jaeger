<?php

namespace App\Models\Auth;
use RZP\Base;


class Validator  \Razorpay\Spine\Validation\Validator
{
    protected static $authorizeRules = [
        'response_type' => 'required|alpha',
        'client_id'     => 'required|alpha_num|max:14',
        'redirect_uri'  => 'sometimes|url',
        'scope'         => 'required|alpha_dash',
        'state'         => 'sometimes'
    ];

    protected static $accessTokenRules = [
    	'client_id'     => 'required|alpha_num|max:14',
        'grant_type'    => 'required|string',
        'client_secret' => 'required|string',
        'redirect_uri'  => 'sometimes|url',
        'code'          => 'required|string',
        'state'         => 'sometimes'
    ];
}
