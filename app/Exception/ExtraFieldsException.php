<?php

namespace App\Exception;

use App\Error\Error;
use App\Error\ErrorCode;

class ExtraFieldsException extends RecoverableException
{
    protected $fields;

    public function __construct(
        $fields,
        $code = ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        \Exception $previous = null)
    {
        $this->fields = $fields;

        $extraFields = $fields;

        if (is_array($fields) === true)
        {
            $extraFields = implode(', ', $fields);
        }

        $message = $extraFields . ' is/are not required and should not be sent';

        $this->error = new Error($code, $message);

        parent::__construct($message, $code, $previous);
    }

    public function getExtraFields()
    {
        return $this->fields;
    }
}
