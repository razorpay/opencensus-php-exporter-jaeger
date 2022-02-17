<?php

namespace App\Providers;

use Trace;
use App\Constants\TraceCode;
use OpenCensus\Trace\Tracer;
use Illuminate\Routing\Router;
use App\Trace\Hypertrace\Tracing;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\URL;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use OpenCensus\Trace\Integrations\Curl;
use App\Constants\Tracing as TracingConstant;
use OpenCensus\Trace\Exporter\JaegerExporter;
use OpenCensus\Trace\Propagator\JaegerPropagator;

class OpenCensusProvider extends ServiceProvider
{
    public function boot()
    {
        if (Tracing::isEnabled($this->app) === false)
        {
            Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_enabled' => false,
            ]);

            return;
        }

        //test to run matched
        $container = new Container;
        $router = new Router(new Dispatcher, $container);

        $router->matched(function($event) {

            $currentRoute = $event->route;

            if (Tracing::shouldTraceRoute($currentRoute) === false)
            {
                Trace::info(TraceCode::JAEGER_INFO, [
                    'jaeger_app_route' => false,
                    'route_name'       => $currentRoute->getName(),
                ]);

                return;
            }
            Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_route' => true,
                'route_name'       => $currentRoute->getName(),
            ]);
            // Load all useful extensions
            Curl::load();

            $spanOptions = $this->getSpanOptions($currentRoute);

            $propagator    = new JaegerPropagator();
            $tracerOptions = [
                'propagator'        => $propagator,
                'root_span_options' => $spanOptions];

            $serviceName = Tracing::getServiceName();

            $jaegerExporterOptions = [
                'host' => $this->app['config']->get('jaeger.host'),
                'port' => $this->app['config']->get('jaeger.port')
            ];

            $exporter = new JaegerExporter($serviceName, $jaegerExporterOptions);
            Tracer::start($exporter, $tracerOptions);
        });
    }

    private function getSpanOptions($route)
    {
        $attrs = Tracing::getBasicSpanAttributes($this->app);

        $attrs[TracingConstant::SPAN_KIND] = TracingConstant::SERVER;

        $attrs[TracingConstant::HTTP . '.' . TracingConstant::URL] = URL::current();

        $parameterMap = Route::current()->parameters();

        $spanName = $this->getSpanName($attrs[TracingConstant::HTTP . '.' . TracingConstant::URL]);

        $spanOptions = [TracingConstant::NAME => $spanName, TracingConstant::ATTRIBUTES => $attrs];

        $routeParamPrefix = TracingConstant::HTTP . '.' . TracingConstant::ROUTE_PARAMS;

        if (empty($parameterMap) === false)
        {
            foreach ($parameterMap as $key => $value)
            {
                $spanOptions[TracingConstant::ATTRIBUTES][$routeParamPrefix . $key] = $value;
            }
        }

        $spanOptions[TracingConstant::ATTRIBUTES]['route_name'] = $route->getName();

        return $spanOptions;
    }

    private function getSpanName($url)
    {
        $urlInfo = parse_url($url);

        $spanName = $urlInfo['path'];

        $explodedPath = explode("/", $spanName);

        $strToReplaceInName = [];

        foreach ($explodedPath as $key => $value)
        {
            $splitArr = explode("_", $value);

            if (strlen($value) === TracingConstant::ID_LENGTH ||
                (sizeof($splitArr) === 2) && strlen($splitArr[1]) === TracingConstant::ID_LENGTH)
            {
                $strToReplaceInName [] = "{id}";
            }
            else
            {
                $strToReplaceInName [] = $value;
            }
        }

        if (empty($strToReplaceInName) === false)
        {
            $spanName = implode("/", $strToReplaceInName);
        }

        return $spanName;
    }

    public function register()
    {
    }
}
