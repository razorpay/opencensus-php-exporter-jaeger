<?php


namespace App\Services\Mock;


class Raven
{
    public function generateOTP(
        string $clientId,
        string $userId,
        string $loginId)
    {
        return ['otp' => '0007'];
    }

    public function verifyOTP(
        string $clientId,
        string $userId,
        string $loginId,
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
