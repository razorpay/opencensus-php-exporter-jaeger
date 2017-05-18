<?php

namespace App\Auth;

use App\Base;

class Validator extends Base\Validator
{
    protected static $authCodeRules = [
        'response_type' => 'required|alpha',
        'client_id'     => 'required|alpha_num|max:14',
        'redirect_uri'  => 'required|url',
        'scope'         => 'required|alpha_space'
    ];

    protected static $accessTokenRules = [
    	'client_id'     => 'required|alpha_num|max:14',
        'grant_type'    => 'required|string',
        'client_secret' => 'required|string',
        'redirect_uri'  => 'required|url',
        'code'          => 'required|string'
    ];
}
