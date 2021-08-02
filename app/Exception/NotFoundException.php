<?php

namespace App\Exception;

use App\Error\ErrorCode;

class NotFoundException extends ServerErrorException
{
    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_NOT_FOUND;

        if ($message === null)
        {
            $message = 'Resource was for found';
        }

        parent::__construct($message, $code, $data, $previous);
    }
}
