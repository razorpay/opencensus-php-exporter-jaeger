<?php

namespace Raven\Exception;

use Raven\Error\Error;
use Raven\Error\ErrorCode;

class GatewayTimeoutException extends RecoverableException
{
    public function __construct($curlErrorMessage, \Exception $previous = null)
    {
        $code = ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT;

        $this->error = new Error($code);

        $this->message = $curlErrorMessage;

        parent::__construct($curlErrorMessage, $code, $previous);
    }
}