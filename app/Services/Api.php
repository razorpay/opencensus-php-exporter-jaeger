<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Request;
use App\Constants\Mode;
use App\Error\ErrorCode;
use App\Constants\RequestParams;
use App\Exception\BadRequestException;
use Trace;
use OpenCensus\Trace\Tracer;
use App\Request\Requests;

use Illuminate\Support\Str;

use App\Constants\TraceCode;
use App\Exception\LogicException;

class Api
{

    const CLIENT_ID          = 'client_id';
    const USER_ID            = 'user_id';
    const MERCHANT_ID        = 'merchant_id';
    const OTP                = 'otp';
    const EMAIL              = 'email';
    const OAUTH_NOTIFY_ROUTE = '/oauth/notify/';
    const USERS_ROUTE        = '/users_unified';

    protected $apiUrl;

    protected $defaultHeaders = [];

    public function __construct()
    {
        $this->apiUrl = env('APP_API_URL');

        $this->defaultHeaders = [RequestParams::DEV_SERVE_USER => Request::header(RequestParams::DEV_SERVE_USER)];
        $this->options = ['auth' => $this->getAuthenticationOption(Mode::LIVE)];
    }

    public function notifyMerchant(
        string $clientId,
        string $userId,
        string $merchantId,
        string $type = 'app_authorized')
    {
        $url = $this->apiUrl . self::OAUTH_NOTIFY_ROUTE . $type;

        $postPayload = [
            self::CLIENT_ID => $clientId,
            self::USER_ID  => $userId,
            self::MERCHANT_ID => $merchantId
        ];

        try
        {
            Requests::post($url, $this->defaultHeaders, $postPayload, $this->options);
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

    public function sendOTPViaEmail(
        string $clientId,
        string $userId,
        string $merchantId,
        string $otp,
        string $email,
        string $type)
    {
        $url = $this->apiUrl . self::OAUTH_NOTIFY_ROUTE . $type;

        $postPayload = [
            self::CLIENT_ID   => $clientId,
            self::USER_ID     => $userId,
            self::MERCHANT_ID => $merchantId,
            self::OTP         => $otp,
            self::EMAIL       => $email
        ];

        try
        {
            $response = Requests::post($url, $this->defaultHeaders, $postPayload, $this->options);

            $apiResponse = json_decode($response->body, true);

            if ($response->status_code === 200 && isset($apiResponse['error']) === false)
            {
                return $apiResponse;
            }
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
            $response = Requests::get($url, $this->defaultHeaders, $this->options);

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

    public function getUserByEmail(string $emailId): array|JsonResponse
    {
        $url = $this->apiUrl . self::USERS_ROUTE;

        $payload = [
            self::EMAIL => $emailId
        ];

        try
        {
            $response = Requests::request($url, $this->defaultHeaders, $payload, options: $this->options);

            $apiResponse = json_decode($response->body, true);
        }
        catch (\Throwable $e)
        {
            $tracePayload = [
                'class'   => get_class($e),
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::USER_DETAILS_FETCH_FAILED, $tracePayload);

            throw new LogicException('Error when fetching user data');
        }

        if ($response->status_code === 200 && isset($apiResponse['error']) === false)
        {
            return $apiResponse;
        }
        if ($response->status_code == 429)
        {
            Trace::info(TraceCode::REQUESTS_GOT_THROTTLED, ['api response' => $apiResponse]);
            return response()->json(['message' => $apiResponse], 429);
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_OR_USER);
    }

    public function getOrgHostName(string $merchantId): string
    {
        $orgDetails = $this->getMerchantOrgDetails($merchantId);
        if (empty($orgDetails['primary_host_name'])) {
            throw new LogicException('primary_host_name missing merchant org details', $orgDetails);
        }

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
            Requests::post($url, $this->defaultHeaders, $postPayload, $this->options);
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
            Requests::delete($url, $this->defaultHeaders, $this->options);
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
        $options = ['auth' => $this->getAuthenticationOption($mode)];
        $url = $this->apiUrl . '/merchant/' . $merchantId . '/banking_accounts/';

        Trace::info(TraceCode::BANKING_ACCOUNTS_WEBHOOK_REQUEST, [
            'url'        => $url,
            'merchantId' => $merchantId,
        ]);

        try
        {
            $response = Tracer::inSpan(['name' => 'triggerBankingAccountsWebhook.post'],
                function() use($url, $options) {
                    return Requests::post($url, $this->defaultHeaders, [], $options);
                });

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

    /**
     * Return authentication option that can be used with Requests
     *
     * @param string $mode
     *
     * @return array - ["username", "password"]
     * @throws \Exception
     */
    private function getAuthenticationOption(string $mode)
    {
        switch ($mode) {
            case Mode::LIVE:
                return [env("APP_API_LIVE_USERNAME"), env("APP_API_LIVE_PASSWORD")];
            case Mode::TEST:
                return [env("APP_API_TEST_USERNAME"), env("APP_API_TEST_PASSWORD")];
            default:
                throw new \Exception("invalid mode supplied: " . $mode);
        }
    }
}
