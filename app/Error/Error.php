<?php

namespace App\Error;

use Illuminate\Support\Fluent;
use App\Exception\InvalidArgumentException;

class Error extends Fluent
{
    const INTERNAL_ERROR_CODE   = 'internal_error_code';
    const INTERNAL_ERROR_DESC   = 'internal_error_desc';
    const PUBLIC_ERROR_CODE     = 'code';
    const HTTP_STATUS_CODE      = 'http_status_code';
    const DESCRIPTION           = 'description';
    const FIELD                 = 'field';
    const ERROR_CLASS           = 'class';
    const DATA                  = 'data';

    protected $attributes = array();

    public function __construct(
        $code,
        $desc = null,
        $field = null,
        $data = null)
    {
        $this->fill($code, $desc, $field, $data);
    }

    public function fill($code, $desc = null, $field = null, $data = null, $internalDesc = null)
    {
        $this->setAttribute(self::DATA, $data);

        $this->setAttribute(self::FIELD, $field);

        $this->setInternalErrorCode($code);

        $this->setClass($code);

        $this->setPublicErrorDetails();

        $this->setDesc($desc);

        $this->setAttribute(self::INTERNAL_ERROR_DESC, $internalDesc);
    }

    protected function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    protected function setInternalErrorCode($code)
    {
        self::checkErrorCode($code);

        $this->setAttribute(self::INTERNAL_ERROR_CODE, $code);
    }

    protected function setClass($code)
    {
        $class = $this->getErrorClassFromErrorCode($code);

        self::checkErrorClass($class);

        $this->setAttribute(self::ERROR_CLASS, $class);
    }

    protected function setDesc(string $desc = null)
    {
        //
        // We get description in this order
        // * From function argument
        // * From description of internal error code
        // * From description of public error code
        //
        // If all 3 above are null, then throw exception
        //
        if ($desc === null)
        {
            $code = $this->getInternalErrorCode();

            $desc = $this->getDescriptionFromErrorCode($code);

            if ($desc === null)
            {
                $code = $this->getPublicErrorCode();

                $desc = $this->getDescriptionFromErrorCode($code);

                if ($desc === null)
                {
                    throw new InvalidArgumentException(
                        'Description not provided for code: '. $code);
                }
            }
        }

        if (is_string($desc) === false)
        {
            throw new InvalidArgumentException('Description should be string');
        }

        $this->setAttribute(self::DESCRIPTION, $desc);
    }

    protected function setPublicErrorCode($code)
    {
        $this->setAttribute(self::PUBLIC_ERROR_CODE, $code);
    }

    protected function setHttpStatusCode($code)
    {
        $this->setAttribute(self::HTTP_STATUS_CODE, $code);
    }

    protected function getAttribute($attr)
    {
        return $this->attributes[$attr];
    }

    protected function setPublicErrorDetails()
    {
        $class = $this->getAttribute(self::ERROR_CLASS);

        switch ($class)
        {
            case ErrorClass::BAD_REQUEST:
                $this->handleBadRequestErrors();
                break;

            case ErrorClass::SERVER:
                $this->setPublicErrorCode(PublicErrorCode::SERVER_ERROR);
                $this->setHttpStatusCode(500);
                break;

            default:
                throw new InvalidArgumentException('Not a valid class');
        }
    }

    public function getPublicError()
    {
        return $this->publicError;
    }

    public function getInternalErrorCode()
    {
        return $this->getAttribute(self::INTERNAL_ERROR_CODE);
    }

    public function getDescription()
    {
        return $this->getAttribute(self::DESCRIPTION);
    }

    public function getClass()
    {
        return $this->getAttribute(self::ERROR_CLASS);
    }

    public function getPublicErrorCode()
    {
        return $this->getAttribute(self::PUBLIC_ERROR_CODE);
    }

    public function getHttpStatusCode()
    {
        return $this->getAttribute(self::HTTP_STATUS_CODE);
    }

    protected function handleBadRequestErrors()
    {
        $code = $this->getInternalErrorCode();

        $httpStatusCode = 400;

        switch($code)
        {
            case ErrorCode::BAD_REQUEST_UNAUTHORIZED:
                $httpStatusCode = 401;
                break;
            case ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED:
                $httpStatusCode = 403;
                break;
        }

        $this->setPublicErrorCode(PublicErrorCode::BAD_REQUEST_ERROR);
        $this->setHttpStatusCode($httpStatusCode);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function toPublicArray()
    {
        $array = array(
            self::PUBLIC_ERROR_CODE     => $this->getPublicErrorCode(),
            self::DESCRIPTION           => $this->getDescription(),
        );

        $field = $this->getAttribute(self::FIELD);

        if ($field !== null)
            $array[self::FIELD] = $field;

        return array('error' => $array);
    }

    public function toDebugArray()
    {
        return [
            'error' => $this->getAttributes()
        ];
    }

    protected function getDescriptionFromErrorCode($code)
    {
        $code = strtoupper($code);

        if (defined(PublicErrorDescription::class . '::' . $code) === true)
        {
            return constant(PublicErrorDescription::class.'::'.$code);
        }
    }

    protected function getErrorClassFromErrorCode($code)
    {
        $pos = strpos($code, '_');

        $class = substr($code, 0, $pos);

        if ($class == 'BAD')
        {
            $class = ErrorClass::BAD_REQUEST;
        }

        return $class;
    }

    public static function checkErrorCode($code)
    {
        if ($code === null)
        {
            throw new InvalidArgumentException('null provided for errorcode');
        }

        if (defined(ErrorCode::class.'::'.$code) === false)
        {
            throw new InvalidArgumentException('ErrorCode: ' . $code . ' is not defined');
        }
    }

    protected static function checkErrorClass($class)
    {
        if (defined(ErrorClass::class.'::'.$class) === false)
        {
            throw new InvalidArgumentException($class . ' is not a valid class');
        }
    }
}
