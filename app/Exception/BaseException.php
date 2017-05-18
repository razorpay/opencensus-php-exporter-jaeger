<?php

namespace Raven\Exception;

class BaseException extends \Exception
{
    protected $error = null;

    protected $data = null;

    /**
     * Constructor for base exception of the
     * application
     *
     * @param string    $message
     * @param string    $code
     * @param \Exception $previous
     */
    public function __construct(
        string $message,
        string $code = '',
        \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->code = $code;
    }

    protected function setError($error)
    {
        $this->error = $error;
    }

    public function setGatewayErrorCodeAndDesc($code, $desc)
    {
        $this->error->setGatewayErrorCodeAndDesc($code, $desc);
    }

    public function getError()
    {
        return $this->error;
    }

    public function getPublicError()
    {
        return $this->error->getPublicError();
    }

    public function generatePublicJsonResponse()
    {
        $error = $this->error;

        $httpStatusCode = $error->getHttpStatusCode();

        return response()->json($this->error->toPublicArray(), $httpStatusCode);
    }

    public function generateDebugJsonResponse()
    {
        $error = $this->error;

        $httpStatusCode = $error->getHttpStatusCode();

        return response()->json($error->toDebugArray(), $httpStatusCode);
    }

    public function getData()
    {
        return $this->data;
    }
}
