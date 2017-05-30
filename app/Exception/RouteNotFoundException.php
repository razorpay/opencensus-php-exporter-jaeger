<?php

namespace App\Exception;

use App\Error\Error;
use App\Error\ErrorCode;

class RouteNotFoundException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $route = null,
        \Exception $previous = null)
    {
        $code = ErrorCode::BAD_REQUEST_URL_NOT_FOUND;

        $this->error = new Error($code, null, $route);

        $message = $this->error->getDescription();

        parent::__construct($message, $code);
    }
}
