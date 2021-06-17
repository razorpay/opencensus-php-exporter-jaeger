<?php


namespace App\Services\Mock;


use App\Error\Error;
use App\Error\ErrorCode;
use App\Exception\BadRequestException;

class Raven
{
    const INVALID_OTP           = 'Invalid OTP';

    public function generateOTP(
        string $loginId,
        string $context)
    {
        return ['otp' => '0007'];
    }

    public function verifyOTP(
        string $loginId,
        string $context,
        string $otp)
    {
        if($otp !== '0007')
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_OTP);
        }
        else
        {
            return ['success' => true];
        }
    }
}
