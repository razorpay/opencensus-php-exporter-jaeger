<?php

namespace App\Models\Auth;

use App\Constants\RequestParams;
use App\Models\Base\JitValidator;

class Validator extends \Razorpay\Spine\Validation\Validator
{
    protected static $authorizeRules = [
        'response_type' => 'required|alpha',
        'client_id'     => 'required|alpha_num|size:14',
        'redirect_uri'  => 'sometimes|url',
        'scope'         => 'required|alpha_dash',
        'state'         => 'sometimes'
    ];

    protected static $accessTokenRules = [
        'client_id'     => 'required|alpha_num|size:14',
        'grant_type'    => 'required|string',
        'client_secret' => 'required|string',
        'redirect_uri'  => 'sometimes|url',
        'code'          => 'required|string',
        'state'         => 'sometimes'
    ];

    public function validateAuthorizeRequest(array $input)
    {
        $rules = [
            RequestParams::STATE        => 'required|string',
            RequestParams::REDIRECT_URI => 'required|url'
        ];

        (new JitValidator)->rules($rules)
                          ->input($input)
                          ->strict(false)
                          ->validate();
    }

    public function validateRequestAccessTokenMigration(array $input)
    {
        $rules = [
            RequestParams::CLIENT_ID           => 'required|alpha_num|size:14',
            RequestParams::MERCHANT_ID         => 'required|alpha_num|size:14',
            RequestParams::USER_ID             => 'required|alpha_num|size:14',
            RequestParams::REDIRECT_URI        => 'required|url',
            RequestParams::PARTNER_MERCHANT_ID => 'required|alpha_num|size:14',
        ];

        (new JitValidator)->rules($rules)
                          ->input($input)
                          ->strict(true)
                          ->validate();
    }
}
