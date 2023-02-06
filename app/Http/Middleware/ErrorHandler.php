<?php

 namespace App\Http\Middleware;

 use App;
 use Closure;
 use Trace;
 use App\Constants\TraceCode;

 // This middleware handles fatal errors to avoid stack traces thrown to end user
 // give out a clear error message rather clumsy strace
class ErrorHandler
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response  $response
     */
    public function handle($request, Closure $next)
    {
        try {
            $response = $next($request);
            return $response;
        } finally {
            $error = $this->getLastError();
            // Fatal error, E_ERROR === 1
            if ($error != null && $error['type'] === E_ERROR) {
                Trace::critical(TraceCode::PHP_FATAL_ERROR, [
                    'file' => $error['file'],
                    'line' => $error['line'],
                    'message' => $error['message'],
                    'stack' => debug_backtrace()
                ]);
                // clear the fatal error
                error_clear_last();
                // deletes the topmost output buffer and all of its contents
                ob_clean();

                $resp = ['error' => ['code' => 'SERVER_ERROR', 'description' => 'The server encountered an error. The incident has been reported to admins']];
                $response = response()->json($resp, 500);
                return $response;
            }
        }
    }

    /**
     * Gets last error occured.
     *
     * @return mixed
     */
    public function getLastError()
    {
        // easier to override in tests
        return error_get_last();
    }
}
