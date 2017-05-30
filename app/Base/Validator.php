<?php

namespace App\Base;

use App\Exception\BadRequestValidationFailureException;
use App\Exception\ExtraFieldsException;
use Razorpay\Spine\Validation\Validator as SpineValidator;

class Validator extends SpineValidator
{
    public static function validateInputKeyExists(array $input, $key)
    {
        if (isset($input[$key]) === false)
        {
            // throw new Exception\BadRequestValidationFailureException(
            //     $key . ' not given in the input');
            throw new \Exception("Bad request validation failed. ".$key." not given in the input.", 1);
        }
    }

    protected function throwExtraFieldsException($extraFields)
    {
        throw new ExtraFieldsException($extraFields);
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new BadRequestValidationFailureException($messages);
    }
}
