<?php

namespace App\Services;

use Requests;

class Dashboard
{
    public function getTokenData(string $token)
    {
        $options = ['auth' => ['rzp_api', env('APP_DASHBOARD_SECRET')]];

        $response = Requests::get(
            env('APP_DASHBOARD_URL') . '/user/token/' . $token . '/details',
            [],
            $options
        );

        return json_decode($response->body, true);
    }
}
