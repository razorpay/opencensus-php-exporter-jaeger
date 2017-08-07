<?php

namespace App\Error;

use App\Exception;

class Map
{
    public static $map = [
        PublicErrorCode::BAD_REQUEST_ERROR  => Exception\BadRequestException::class,
        PublicErrorCode::SERVER_ERROR       => Exception\ServerErrorException::class
    ];

    public static function throwExceptionFromErrorDetails($publicCode, $internalCode, $desc)
    {
        $class = null;

        if (in_array($publicCode, self::$map, true) === true)
        {
            $class = self::$map[$publicCode];
        }

        if ($internalCode === ErrorCode::BAD_REQUEST_VALIDATION_FAILURE)
        {
            throw new Exception\BadRequestValidationFailureException($desc);
        }
        else if ($class === Exception\BadRequestException::class)
        {
            throw new Exception\BadRequestException($internalCode);
        }
        else if ($publicCode === PublicErrorCode::SERVER_ERROR)
        {
            throw new Exception\ServerErrorException(
                'Server error getting repeated for payment callback',
                ErrorCode::SERVER_ERROR);
        }
    }
}