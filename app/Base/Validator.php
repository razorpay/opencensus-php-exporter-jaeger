<?php

namespace App\Base;

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
}