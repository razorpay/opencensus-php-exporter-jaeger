<?php

namespace App\Services;

use Trace;
use Requests;

use Illuminate\Support\Str;

use App\Constants\TraceCode;
use App\Exception\LogicException;

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

    public function getMerchantOrgDetails(string $merchantId): array
    {
        $url = $this->apiUrl . '/merchants/' . $merchantId . '/org';

        try
        {
            $response = Requests::get($url, [], $this->options);

            return json_decode($response->body, true);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::ORG_DETAILS_FETCH_FAILED, $tracePayload);
        }

        throw new LogicException('Error when fetching org data');
    }

    public function getOrgHostName(string $merchantId): string
    {
        $orgDetails = $this->getMerchantOrgDetails($merchantId);

        $protocolIdentifier = '';

        if (Str::startsWith($orgDetails['primary_host_name'], 'http') === false)
        {
            $protocolIdentifier = ((env('APP_ENV') === 'dev')
                                   or (env('APP_ENV') === 'docker')) ? 'http://' : 'https://';
        }

        return $protocolIdentifier.$orgDetails['primary_host_name'];
    }

    public function mapMerchantToApplication(string $appId, string $merchantId, string $partnerId)
    {
        $url = $this->apiUrl . '/merchants/' . $merchantId . '/applications';

        $postPayload = [
            'application_id'   => $appId,
            'partner_id'       => $partnerId,
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
