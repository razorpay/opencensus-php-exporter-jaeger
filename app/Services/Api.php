<?php

namespace App\Services;

use Requests;

class Api
{
    public function notifyMerchant(string $clientId, string $userId)
    {
        $options = [
            'auth' => ['rzp_test', env('APP_API_SECRET')]
        ];

        $data = ['client_id' => $clientId, 'user_id' => $userId];

        $response = Requests::post(
            env('APP_API_URL') . '/oauth/notify/authorize',
            [],
            $data,
            $options
        );

        return;
    }
}
