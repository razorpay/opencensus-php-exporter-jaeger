<?php


namespace App\Http\Middleware;


use App;
use Closure;
use Trace;
use App\Constants\TraceCode;

class EventTracker
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        $this->sendEventsToSegmentAnalytics();
    }

    protected function sendEventsToSegmentAnalytics()
    {
        try
        {
            $this->app['segment-analytics']->buildRequestAndSend();
        }
        catch (\Throwable $e)
        {
            Trace::critical(TraceCode::SEGMENT_EVENT_PUSH_FAILURE, [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
