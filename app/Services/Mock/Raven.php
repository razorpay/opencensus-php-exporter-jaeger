<?php


namespace App\Services\Mock;


class Raven
{
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
            throw new LogicException('Invalid OTP');
        }
        else
        {
            return ['success' => true];
        }
    }
}
