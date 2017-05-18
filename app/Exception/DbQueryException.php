<?php

namespace App\Exception;

// use DB;
use App\Error\ErrorCode;
use Razorpay\Spine\Exception\DbQueryExceptionTrait;

class DbQueryException extends ServerErrorException
{
    use DbQueryExceptionTrait;

    /**
     * Aim should be to fill the value of these attributes.
     *
     * @var array
     */
    protected $fields = [
        'operation',
        'model',
        'attributes',
        'query'
    ];

    public function __construct(array $data, \Exception $previous = null)
    {
        $code = ErrorCode::SERVER_ERROR_DB_QUERY_FAILED;

        $message = $this->constructMessage($data);

        parent::__construct($message, $code, $data, $previous);
    }
}
