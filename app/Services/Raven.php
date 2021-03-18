<?php

use Trace;
use Requests;
use App\Constants\TraceCode;
use App\Exception\LogicException;

class Raven
{
    protected $ravenUrl;

    protected $secret;

    public function __construct()
    {
        $this->ravenUrl = env('APP_RAVEN_URL');
        $this->secret = env('APP_RAVEN_SECRET');

        $this->options = ['auth' => ['rzp_live', $this->secret]];
    }

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

            Requests::post($url, [], $postPayload, $this->options);

        } catch (\Throwable $e) {

            $tracePayload = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::RAVEN_GENERATE_OTP_FAILED, $tracePayload);
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

            $isSuccess = json_decode($response->body, true)['success'];

            if($isSuccess === false)
            {
                throw new LogicException('Invalid OTP');
            }

        } catch (\Throwable $e) {

            $tracePayload = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::critical(TraceCode::RAVEN_VERIFY_OTP_FAILED, $tracePayload);
        }
    }
}
