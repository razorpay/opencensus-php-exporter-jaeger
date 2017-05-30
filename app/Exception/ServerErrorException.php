<?php

namespace App\Exception;

use App\Error\Error;

class ServerErrorException extends BaseException
{
    protected $fields = [];

    protected $code = null;

    public function __construct(
        $message,
        $code,
        $data = null,
        \Exception $previous = null)
    {
        $this->data = $data;

        $error = new Error($code, null, null, $data, $message);

        $this->error = $error;

        parent::__construct($message, $code, $previous);
    }
}
