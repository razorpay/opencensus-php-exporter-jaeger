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
                    "name" => "Test User Account",
                    "email" => "test@razorpay.com",
                    "contact_mobile" => "9999999999",
                    "contact_mobile_verified" => false,
                    "account_locked" => false,
                    "confirmed" => true,
                    "merchants" => [
                        [
                            "gstin" => null,
                            "pan" => null,
                            "billing_address" => [
                            "line1" => null,
                                "line2" => null,
                                "city" => null,
                                "state" => null,
                                "country" => "India",
                                "zipcode" => null
                            ],
                            "description" => null,
                            "id" => "10000000000000",
                            "activated" => true,
                            "website" => null,
                            "name" => "Test Account",
                            "billing_label" => "Test Account"
                        ]
                    ],
                    "id" => "20000000000000"
                ];
        }
        throw new LogicException('Error when fetching user data');
    }

    public function sendOTPViaMail(
        string $clientId,
        string $userId,
        string $merchantId,
        string $otp,
        string $email,
        string $type = 'native_auth_otp')
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
