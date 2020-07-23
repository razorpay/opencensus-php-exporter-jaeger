<?php

namespace App\Http\Middleware;

use Closure;
use App\Constants\Metric;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Metrics
{
    public function handle($request, Closure $next) {
        $start = millitime();

        $response = $next($request);

        $duration = millitime() - $start;

        $this->pushHttpMetrics($request, $response, $duration);

        return $response;
    }

    protected function pushHttpMetrics(Request $request, Response $response, $duration)
    {
        $dimensions = $this->getMetricDimensions($request, $response);

        app('trace')->count(Metric::HTTP_REQUESTS_TOTAL, $dimensions);
        app('trace')->histogram(Metric::HTTP_REQUEST_DURATION_MILLISECONDS, $duration, $dimensions);
    }

    protected function getMetricDimensions(Request $request, Response $response): array
    {
        return [
            Metric::LABEL_METHOD                => $request->getMethod(),
            Metric::LABEL_ROUTE                 => $this->getRouteName($request),
            Metric::LABEL_STATUS                => $response->getStatusCode(),
        ];
    }

    protected function getRouteName(Request $request)
    {
        $route = $request->route();

        if (empty($route[1]['as']) === true) {
            return 'other';
        }

        return $route[1]['as'];
}
}
