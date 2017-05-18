<?php

namespace App\Exception;

use App\Error\Error;
use App\Error\ErrorCode;

class InvalidArgumentException extends ServerErrorException
{

    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_INVALID_ARGUMENT;

        if ($message === null)
        {
            $message = 'Invalid argument provided';
        }

        parent::__construct($message, $code, $data, $previous);
    }
}
