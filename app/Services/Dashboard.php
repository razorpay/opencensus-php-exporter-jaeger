<?php

namespace App\Services;

use Requests;

class Dashboard
{
    protected $config;
    protected $trace;

    public function __construct($app)
    {
        $this->trace = $app['trace'];
    }

    public function getTokenData(string $token)
    {
        $options = ['auth' => ['rzp_api', env('APP_DASHBOARD_SECRET')]];

        $response = Requests::get(
            env('APP_DASHBOARD_URL') . 'user/token/' . $token . '/details',
            [],
            $options
        );

        return json_decode($response->body, true);
    }

}
