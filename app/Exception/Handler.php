<?php

namespace App\Exception;

use App;
use Razorpay\Trace\Trace;
use Response;
use App\Error\Error;
use App\Error\ErrorCode;
use Psr\Log\LoggerInterface;
use App\Constants\TraceCode;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    public function __construct(LoggerInterface $log)
    {
        parent::__construct($log);

        $this->app = App::getFacadeRoot();
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(\Exception $e)
    {
        // Trace Exceptions here
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $e
     *
     * @return \Illuminate\Http\Response
     * @throws RouteNotFoundException
     */
    public function render($request, \Exception $e)
    {
        switch (true)
        {
            case $e instanceOf NotFoundHttpException:
                throw new RouteNotFoundException();
            case $e instanceof UnauthorizedException:
                $error = $e->getError();
                return response()->json(
                    $error->toPublicArray(),
                    $error->getHttpStatusCode());

            case $e instanceof BaseException:
                return $this->baseExceptionHandler($e);

            case $e instanceof ProcessTimedOutException:
                return response()->json(['error' => 'Process timed out']);

            case $e instanceof MethodNotAllowedHttpException:
                return $this->methodNotFoundResponse();
        }

        return $this->genericExceptionHandler($e);
    }

    protected function genericExceptionHandler(\Exception $exception)
    {
        if ($this->isToStringException($exception))
        {
            return $this->toStringExceptionResponse($this->isDebug(), $exception);
        }

        $this->traceException($exception);

        //
        // When running in console, throw the exception, irrespective
        // of debug config
        //
        $this->ifTestingThenRethrowException($exception);

        return $this->generateServerErrorResponse($this->isDebug(), $exception);
    }

    protected function baseExceptionHandler(BaseException $exception)
    {
        // ServerError is fatal error and shoudn't be encountered
        // Let the higher-ups handle it. This function handles
        // known/expected exceptions
        if ($exception instanceof ServerErrorException)
            return;

        \Trace::warn(
            TraceCode::RECOVERABLE_EXCEPTION,
            $this->getExceptionDetails($exception));

        return $this->recoverableErrorResponse($this->isDebug(), $exception);
    }

    public function traceException(\Exception $exception, $level = null, $code = null)
    {
        $traceData = $this->getExceptionDetails($exception);

        if (($level === null) and
            ($code === null))
        {
            dd ($exception);
            if ($exception instanceof RecoverableException)
            {
                $level = Trace::WARNING;
                $code = TraceCode::RECOVERABLE_EXCEPTION;
            }
            else
            {
                $level = Trace::ERROR;
                $code = TraceCode::ERROR_EXCEPTION;
            }
        }

        \Trace::addRecord($level, $code, $traceData);
    }

    protected function getExceptionDetails(\Exception $exception, $level = 0)
    {
        $previousException = $exception->getPrevious();

        $previous = null;

        if ($previousException !== null)
        {
            $previous = $this->getExceptionDetails($previousException, $level + 1);
        }

        $data = null;

        if (method_exists($exception, 'getData'))
        {
            $data = $exception->getData();

            if (is_array($data) === false)
            {
                $data = null;
            }
        }

        $stack = explode("\n", $exception->getTraceAsString());

        if ($level > 0)
        {
            $stack = array_slice($stack, 0, 5);
        }

        //
        // @note: Always call function 'getTraceAsSring' to get stack trace
        //        since it doesn't include function arguments.
        //        Function arguments can contain sensitive data so should
        //        never be logged. Never call 'getTrace' directly.
        //
        // @note: Don't remove this comment.
        //
        $traceData = array(
            'class'     => get_class($exception),
            'code'      => $exception->getCode(),
            'message'   => $exception->getMessage(),
            'data'      => $data,
            'stack'     => $stack,
            'previous'  => $previous);

        return $traceData;
    }

    protected function isToStringException($exception)
    {
        $message = $exception->getMessage();

        $str = 'Swift_Message::__toString()';

        if (strpos($message, $str) === false)
        {
            return false;
        }

        \Trace::warn(
            TraceCode::MISC_TOSTRING_ERROR,
            $this->getExceptionDetails($exception));

        return true;
    }

    public function getErrorResponseFields($code)
    {
        $error = new Error($code);

        $publicError = $error->toPublicArray();

        $httpStatusCode = $error->getHttpStatusCode();

        return array($publicError, $httpStatusCode);
    }

    protected function methodNotFoundResponse()
    {
        list($publicError, $httpStatusCode) =
                    $this->getErrorResponseFields(ErrorCode::BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED);

        return response()->json($publicError, $httpStatusCode);
    }

    protected function generateServerErrorResponse($debug, $exception)
    {
        list($publicError, $httpStatusCode) =
                $this->getErrorResponseFields(ErrorCode::SERVER_ERROR);

        if (($debug) and
            ($exception !== null))
        {
            $publicError['exception'] = $this->getExceptionData($exception);

            if (method_exists($exception, 'getData'))
            {
                $publicError['data'] = $exception->getData();
            }
        }

        return response()->json($publicError, $httpStatusCode);
    }

    public function toStringExceptionResponse($debug, $exception)
    {
        list($publicError, $httpStatusCode) =
            $this->getErrorResponseFields(ErrorCode::SERVER_ERROR_TO_STRING_EXCEPTION);

        if ($debug)
        {
            $publicError['error']['internal_error_code'] =
                ErrorCode::SERVER_ERROR_TO_STRING_EXCEPTION;
        }

        return response()->json($publicError, $httpStatusCode);
    }

    public function recoverableErrorResponse($debug, $exception = null)
    {
        $this->ifTestingThenRethrowException($exception);

        $error = $exception->getError();

        $httpStatusCode = $error->getHttpStatusCode();

        $data = $debug ? $error->toDebugArray() : $error->toPublicArray();

        return response()->json($data, $httpStatusCode);
    }

    protected function getExceptionData($exception)
    {
        $previous = $exception->getPrevious();
        $previousData = null;

        if ($previous !== null)
            $previousData = self::getExceptionData($previous);

        $data = array(
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'previous' => $previousData,
        );

        $data['trace'] = str_replace('/', "\\", $data['trace']);
        $data['file'] = str_replace('/', "\\", $data['file']);

        return $data;
    }

    protected function ifTestingThenRethrowException($e)
    {
        if ($this->isTesting())
        {
            throw $e;
        }
    }

    public function isTesting()
    {
        return ($this->app->runningUnitTests());
    }

    protected function isDebug()
    {
        return config('app.debug');
    }
}
