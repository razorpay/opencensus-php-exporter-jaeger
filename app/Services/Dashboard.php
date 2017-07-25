<?php

namespace App\Services;

use Requests;

class Dashboard
{
    public function getTokenData(string $token)
    {
        $options = [
            'auth' => ['rzp_auth', env('APP_DASHBOARD_SECRET')]
        ];

        $response = Requests::get(
            env('APP_DASHBOARD_URL') . '/user/token/' . $token . '/details',
            [],
            $options
        );

        // TODO: Handle failures

        $body = $response->body;

        $jsonBody = json_decode($body, true);

        return $jsonBody['data'];
    }
}
