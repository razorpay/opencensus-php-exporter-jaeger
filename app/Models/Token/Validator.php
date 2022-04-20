<?php

namespace App\Models\Token;

use App\Constants\RequestParams;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    protected static $revokeByPartnerRules = [
        RequestParams::CLIENT_ID           => 'required|string',
        RequestParams::CLIENT_SECRET       => 'required|string',
        RequestParams::TOKEN               => 'required|string',
        RequestParams::TOKEN_TYPE_HINT     => 'required|string',
    ];
}
