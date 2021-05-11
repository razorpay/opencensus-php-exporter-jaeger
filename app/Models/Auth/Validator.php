<?php

namespace App\Models\Auth;

use App\Constants\RequestParams;
use App\Exception\BadRequestValidationFailureException;
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

    public static $nativeAccessTokenRequestRules = [
        RequestParams::CLIENT_ID     => 'required|alpha_num|size:14',
        RequestParams::CLIENT_SECRET => 'required|string',
        RequestParams::MERCHANT_ID   => 'required|alpha_num|size:14',
        RequestParams::LOGIN_ID      => 'required|email',
        RequestParams::GRANT_TYPE    => 'required|string|in:native_authorization_code',
        RequestParams::PIN           => 'required'
    ];

    public static $nativeAuthorizeRequestRules = [
        RequestParams::CLIENT_ID   => 'required|alpha_num|size:14',
        RequestParams::MERCHANT_ID => 'required|alpha_num|size:14',
        RequestParams::LOGIN_ID    => 'required|email',
    ];

    public function validateRequest(array $input, array $rules)
    {
        try
        {
            (new JitValidator)->rules($rules)
                ->input($input)
                ->strict(false)
                ->validate();

        }catch (\Throwable $e)
        {
            // Throw a validation failure instead of server error
            throw new BadRequestValidationFailureException($e->getMessage());
        }

    }


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
