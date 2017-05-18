<?php

namespace App\Models\Base;

class ExtendedValidations extends \Razorpay\Spine\Validation\LaravelValidatorEx
{
    protected function validatePublicId($attribute, $id)
    {
        $match = preg_match('/\b[a-z]{0,5}_[a-zA-Z0-9]{14}\b/', $id);

        //
        // This should be compared against 1 and not 0 because
        // preg_match returns either 0 or false in case of failure.
        //
        if ($match !== 1)
        {
            // throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
            throw new Exception("Bad request invalid id", 1);
        }

        return true;
    }

    /**
     * Validates if the value is epoch.
     * By default it checks if the value is in between Jan 2000 - Jan 2100.
     * Parameters(min and max value) can be passed when using this rule.
     *
     * Eg usage:
     * epoch:946684800,946684801
     * epoch
     *
     * @param string $attribute
     * @param mixed  $value
     * @param array  $parameters
     *
     * @return boolean
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateEpoch(string $attribute, $value, array $parameters)
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);

        if ($value === false)
        {
            throw new Exception\BadRequestValidationFailureException("$attribute must be an integer.");
        }

        array_walk(
            $parameters,
            function (& $v, $i)
            {
                $v = intval($v);
            });

        $min = $parameters[0] ?? 946684800;  // 01 January 2000 GMT
        $max = $parameters[1] ?? 4102444800; // 01 January 2100 GMT

        $isValid = (($value >= $min) and ($value <= $max));

        if ($isValid === false)
        {
            throw new Exception\BadRequestValidationFailureException("$attribute must be between $min and $max");
        }

        return true;
    }
}
