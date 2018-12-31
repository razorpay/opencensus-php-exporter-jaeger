<?php

namespace App\Services;

use Trace;
use Requests;

use App\Constants\TraceCode;

class Api
{
    protected $apiUrl;

    protected $secret;

    public function __construct()
    {
        $this->apiUrl = env('APP_API_URL');
        $this->secret  = env('APP_API_SECRET');

        $this->options = ['auth' => ['rzp_live', $this->secret]];
    }

    public function notifyMerchant(
        string $clientId,
        string $userId,
        string $merchantId,
        string $type = 'app_authorized')
    {
        $url = $this->apiUrl . '/oauth/notify/' . $type;

        $postPayload = [
            'client_id'   => $clientId,
            'user_id'     => $userId,
            'merchant_id' => $merchantId
        ];

        try
        {
            Requests::post($url, [], $postPayload, $this->options);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::MERCHANT_NOTIFY_FAILED, $tracePayload);
        }
    }

    public function mapMerchantToApplication(string $appId, string $merchantId, string $partnerId)
    {
        $url = $this->apiUrl . '/merchants/' . $merchantId . '/applications';

        $postPayload = [
            'application_id'   => $appId,
            'partner_id'       => $partnerId
        ];

        try
        {
            Requests::post($url, [], $postPayload, $this->options);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::MERCHANT_APP_MAPPING_FAILED, $tracePayload);
        }
    }

    public function revokeMerchantApplicationMapping(string $appId, string $merchantId)
    {
        $url = $this->apiUrl . '/merchants/' . $merchantId . '/applications/' . $appId;

        try
        {
            Requests::delete($url, [], $this->options);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::MERCHANT_APP_MAPPING_FAILED, $tracePayload);
        }
    }
}
