<?php

namespace App\Services;

use Trace;
use Requests;

use App\Constants\TraceCode;

class Api
{
    public function notifyMerchant(
        string $clientId,
        string $userId,
        string $merchantId,
        string $type = 'app_authorized')
    {
        $apiUrl = env('APP_API_URL');
        $secret = env('APP_API_SECRET');

        $url = $apiUrl . '/oauth/notify/' . $type;

        $options = ['auth' => ['rzp_live', $secret]];

        $postPayload = [
            'client_id'   => $clientId,
            'user_id'     => $userId,
            'merchant_id' => $merchantId
        ];

        try
        {
            Requests::post($url, [], $postPayload, $options);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::error(TraceCode::MERCHANT_NOTIFY_FAILED, $tracePayload);
        }
    }
}
