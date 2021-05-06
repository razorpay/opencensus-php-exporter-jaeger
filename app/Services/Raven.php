<?php

namespace App\Services;

use Trace;
use Requests;
use App\Constants\TraceCode;
use App\Exception\LogicException;
use App\Exception\BadRequestException;
use App\Exception\BadRequestValidationFailureException;

class Raven
{
    const SOURCE_TALLY       = 'native_auth.tally.accounting_payouts';
    const OTP_GENERATE_ROUTE = '/otp/generate';
    const OTP_VERIFY_ROUTE   = '/otp/verify';

    protected $ravenUrl;

    protected $secret;

    public function __construct()
    {
        $this->ravenUrl = env('APP_RAVEN_URL');

        $this->secret = env('APP_RAVEN_SECRET');

        $this->options = ['auth' => ['rzp', $this->secret]];
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

            $response = Requests::post($url, [], $postPayload, $this->options);

            return json_decode($response->body, true);

        } catch (\Throwable $e) {

            $tracePayload = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::RAVEN_GENERATE_OTP_FAILED, $tracePayload);

            throw $e;
        }
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

            $response = Requests::post($url, [], $postPayload, $this->options);

            return json_decode($response->body, true);

        } catch (\Throwable $e) {

            $tracePayload = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::RAVEN_VERIFY_OTP_FAILED, $tracePayload);

            throw $e;
        }
    }
}
