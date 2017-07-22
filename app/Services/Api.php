<?php

namespace App\Services;

use Requests;
use Trace;

use App\Constants\TraceCode;

class Api
{
    public function notifyMerchant(string $clientId, string $userId, string $type = 'app_authorized')
    {
        $options = [
            'auth' => ['rzp_live', env('APP_API_SECRET', '')]
        ];

        $data = ['client_id' => $clientId, 'user_id' => $userId];

        $url = env('APP_API_URL') . '/oauth/notify/' . $type;

        try
        {
            Requests::post($url, [], $data, $options);
        }
        catch (\Exception $e)
        {
            $exception = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage()
            ];

            Trace::error(TraceCode::MERCHANT_NOTIFY_FAILED, $exception);
        }
    }
}
