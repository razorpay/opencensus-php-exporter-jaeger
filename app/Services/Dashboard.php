<?php

namespace App\Services;

use App\Error\ErrorCode;
use App\Exception\BadRequestException;
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

        if (isset($jsonBody['data']) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_CLIENT_OR_USER);
        }

        return $jsonBody['data'];
    }
}
