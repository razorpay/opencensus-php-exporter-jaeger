<?php

namespace App\Exception;

use App\Error\Error;

class UnauthorizedException extends RecoverableException
{
    use MessageFormats;

    public function __construct(
        $code,
        $field = null,
        \Exception $previous = null)
    {
        $this->error = new Error($code, null, $field);

        $message = $this->error->getDescription();

        parent::__construct($message, $code);
    }
}
