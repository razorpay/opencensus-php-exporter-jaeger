<?php

namespace App\Services;

use App\Error\ErrorCode;
use App\Constants\RequestParams;

use Trace;
use Request;
use WpOrg\Requests\Requests;
use App\Constants\TraceCode;
use App\Exception\BadRequestException;

class Raven
{
    const SOURCE_TALLY          = 'tally.accounting_integration';
    const OTP_GENERATE_ROUTE    = '/otp/generate';
    const OTP_VERIFY_ROUTE      = '/otp/verify';

    protected $ravenUrl;

    protected $secret;
    protected $defaultHeaders;

    public function __construct()
    {
        $this->ravenUrl = env('APP_RAVEN_URL');

        $this->secret = env('APP_RAVEN_SECRET');

        $this->options = ['auth' => ['rzp', $this->secret]];

        $this->defaultHeaders = [RequestParams::DEV_SERVE_USER => Request::header(RequestParams::DEV_SERVE_USER)];
    }

    /**
     * @param string $clientId
     * @param string $userId
     * @param string $loginId
     * @return mixed
     */
    public function generateOTP(
        string $loginId,
        string $context)
    {
        $url = $this->ravenUrl . self::OTP_GENERATE_ROUTE;

        $postPayload = [
            'context'  => $context,
            'receiver' => $loginId,
            'source'   => self::SOURCE_TALLY
        ];

        try {

            $response = Requests::post($url, $this->defaultHeaders, $postPayload, $this->options);

            $ravenResponse = json_decode($response->body, true);

        } catch (\Throwable $e) {

            $tracePayload = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::RAVEN_GENERATE_OTP_FAILED, $tracePayload);

            throw $e;
        }

        if ($response->status_code === 200 && isset($ravenResponse['error']) === false)
        {
            return $ravenResponse;
        }

        Trace::critical(TraceCode::RAVEN_GENERATE_OTP_FAILED, $ravenResponse);

        throw new BadRequestException(ErrorCode::BAD_REQUEST_OTP_GENERATION_FAILED);
    }

    public function verifyOTP(
        string $loginId,
        string $context,
        string $otp)
    {
        $url = $this->ravenUrl . self::OTP_VERIFY_ROUTE;

        $postPayload = [
            'context'  => $context,
            'receiver' => $loginId,
            'source'   => self::SOURCE_TALLY,
            'otp'      => $otp
        ];

        try {
            $response = Requests::post($url, $this->defaultHeaders, $postPayload, $this->options);

            $ravenResponse = json_decode($response->body, true);

        } catch (\Throwable $e) {

            $tracePayload = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::RAVEN_VERIFY_OTP_FAILED, $tracePayload);

            throw $e;
        }

        if ($response->status_code === 200 && isset($ravenResponse['error']) === false)
        {
            return $ravenResponse;
        }

        Trace::critical(TraceCode::RAVEN_VERIFY_OTP_FAILED, $ravenResponse);

        throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_OTP);
    }
}
