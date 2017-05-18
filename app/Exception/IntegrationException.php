<?php

namespace App\Exception;

use App\Error\Error;
use App\Error\ErrorCode;

class IntegrationException extends ServerErrorException
{
    public function __construct(
        $message = null,
        $data = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_INTEGRATION_ERROR;

        if ($message === null)
        {
            $message = 'Error occured with one of the service integrations';
        }

        parent::__construct($message, $code, $data, $previous);
    }
}
