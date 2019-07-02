<?php

namespace App\Services;

use Requests;

use App\Models\Auth;
use App\Error\ErrorCode;
use App\Exception\BadRequestException;

class Dashboard
{
    public function getTokenData(string $token, $merchantId)
    {
        $apiService = (new Auth\Service)->getApiService();

        $dashBoardUrl = $apiService->getOrgHostName($merchantId);
        $secret       = env('APP_DASHBOARD_SECRET');

        $url = $dashBoardUrl . '/user/token/' . $token . '/details';

        $options = ['auth' => ['rzp_auth', $secret]];

        $response = Requests::get($url, [], $options);

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
