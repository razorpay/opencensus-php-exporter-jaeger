<?php

namespace App\Services\Mock;

use App\Exception\LogicException;

class Api
{
    public function __call($name, $arguments)
    {
        return;
    }

    public function getUserByEmail(string $login_id): array
    {
        if($login_id === 'test@razorpay.com')
        {
            return [
                "id" => "20000000000000",
                "name" => "Test User Account",
                "email" => "test@razorpay.com",
                "merchants" => [
                    [
                        "id" => "10000000000000",
                        "name" => "Test Account",
                        "billing_label" => "Test Account",
                        "email" => "test@razorpay.com",
                        "activated" => false,
                        "activated_at" => null,
                        "archived_at" => null,
                        "suspended_at" => null,
                        "has_key_access" => false,
                        "logo_url" => null,
                        "display_name" => null,
                        "refund_source" => "balance",
                        "partner_type" => null,
                        "restricted" => false,
                        "created_at" => 1623844064,
                        "updated_at" => 1623844064,
                        "second_factor_auth" => false,
                        "role" => "owner",
                        "product" => "primary",
                        "banking_role" => null
                    ],
                    [
                        "id" => "10000000000001",
                        "name" => "Test Account",
                        "billing_label" => "Test Account",
                        "email" => "test@razorpay.com",
                        "activated" => false,
                        "activated_at" => null,
                        "archived_at" => null,
                        "suspended_at" => null,
                        "has_key_access" => false,
                        "logo_url" => null,
                        "display_name" => null,
                        "refund_source" => "balance",
                        "partner_type" => null,
                        "restricted" => false,
                        "created_at" => 1623844064,
                        "updated_at" => 1623844064,
                        "second_factor_auth" => false,
                        "role" => "admin",
                        "product" => "primary",
                        "banking_role" => null
                    ]
                ]
            ];
        }
        throw new LogicException('Error when fetching user data');
    }

    public function sendOTPViaEmail(
        string $clientId,
        string $userId,
        string $merchantId,
        string $otp,
        string $email,
        string $type)
    {
        if($email === 'test@razorpay.com')
        {
            return [
                "success" => true,
            ];
        }
        return [
            "success" => false,
        ];
    }
}
