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
        string $clientId,
        string $userId,
        string $loginId)
    {
        $url = $this->ravenUrl . '/otp/generate';

        $postPayload = [
            'context' => $userId . '_' . $clientId,
            'receiver' => $loginId,
            'source' => 'native_auth.tally.accounting_payouts'
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
        string $clientId,
        string $userId,
        string $loginId,
        string $otp)
    {
        $url = $this->ravenUrl . '/otp/verify';

        $postPayload = [
            'context' => $userId . '_' . $clientId,
            'receiver' => $loginId,
            'source' => 'native_auth.tally.accounting_payouts',
            'otp'   => $otp
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
