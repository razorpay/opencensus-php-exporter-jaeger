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

    public function sendOTPViaMail(
        string $clientId,
        string $userId,
        string $merchantId,
        string $otp,
        string $email,
        string $type = 'native_auth_otp')
    {
        $url = $this->apiUrl . '/oauth/notify/' . $type;

        $postPayload = [
            'client_id'   => $clientId,
            'user_id'     => $userId,
            'merchant_id' => $merchantId,
            'otp'   => $otp,
            'email' => $email
        ];

        try
        {
            $response = Requests::post($url, [], $postPayload, $this->options);

            return json_decode($response->body, true);
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

        throw new LogicException('Error when sending OTP via mail.');
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

    public function getUserByEmail(string $login_id): array
    {
        $url = $this->apiUrl . '/users';

        $payload = [
            'email'   => $login_id
        ];

        try
        {
            $response = Requests::request($url, [], $payload, Requests::GET, $this->options);

            $userResponse = json_decode($response->body, true);

            if(array_pull($userResponse, 'error', null) !== null)
            {
                throw new LogicException('Invalid user');
            }

            return $userResponse;
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::USER_DETAILS_FETCH_FAILED, $tracePayload);
        }

        throw new LogicException('Error when fetching user data');
    }

    public function getOrgHostName(string $merchantId): string
    {
        $orgDetails = $this->getMerchantOrgDetails($merchantId);

        $protocolIdentifier = '';

        if (Str::startsWith($orgDetails['primary_host_name'], 'http') === false)
        {
            $protocolIdentifier = ((env('APP_MODE') === 'dev')
                                   or (env('APP_MODE') === 'docker')) ? 'http://' : 'https://';
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

    public function triggerBankingAccountsWebhook(string $merchantId, string $mode)
    {
        if($mode === 'test')
        {
            $this->options = ['auth' => ['rzp_test', $this->secret]];
        }

        $url = $this->apiUrl . '/merchant/' . $merchantId . '/banking_accounts/';

        Trace::info(TraceCode::BANKING_ACCOUNTS_WEBHOOK_REQUEST, [
            'url'        => $url,
            'merchantId' => $merchantId,
        ]);

        try
        {
            $response = Requests::post($url, [], [], $this->options);

            return json_decode($response->body, true);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::MERCHANT_BANKING_ACCOUNTS_WEBHOOK_FAILED, $tracePayload);
        }

        throw new LogicException('Error when triggering merchant banking webhooks');
    }
}
