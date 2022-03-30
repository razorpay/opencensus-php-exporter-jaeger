<?php

namespace App\Http\Middleware;

use App;
use Closure;
use App\Constants\TraceCode;
use OpenCensus\Trace\Tracer;
use Illuminate\Http\Request;
use App\Trace\Hypertrace\Tracing;
use Razorpay\Trace\Facades\Trace;
use Illuminate\Support\Facades\URL;
use OpenCensus\Trace\Integrations\PDO;
use OpenCensus\Trace\Integrations\Curl;
use OpenCensus\Trace\Integrations\Mysql;
use App\Constants\Tracing as TracingConstant;
use OpenCensus\Trace\Exporter\JaegerExporter;
use OpenCensus\Trace\Propagator\JaegerPropagator;

class HyperTracer
{
    protected $app;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Tracing::isEnabled($this->app) === false)
        {
            Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_enabled' => false,
            ]);

            return $next($request);
        }
        $routeName = $this->fetchRouteName($request);

        if (Tracing::shouldTraceRoute($routeName) === false)
        {
            Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_route' => false,
                'route_name'       => $routeName,
            ]);

            return $next($request);
        }
        Trace::info(TraceCode::JAEGER_INFO, [
            'jaeger_app_route' => true,
            'route_name'       => $routeName,
        ]);
        // Load all useful extensions
        Mysql::load();
        Curl::load();
        PDO::load();

        $attrs                             = Tracing::getBasicSpanAttributes($this->app);
        $attrs[TracingConstant::SPAN_KIND] = TracingConstant::SERVER;

        $spanOptions = $this->getSpanOptions($request, $routeName);

        $propagator    = new JaegerPropagator();
        $tracerOptions = [
            'propagator'        => $propagator,
            'root_span_options' => $spanOptions];

        $serviceName = Tracing::getServiceName($this->app);

        $jaegerExporterOptions = [
            'host' => $this->app['config']->get('jaeger.host'),
            'port' => $this->app['config']->get('jaeger.port')
        ];

        $exporter = new JaegerExporter($serviceName, $jaegerExporterOptions);
        Trace::info(TraceCode::TALLY_TOKEN_REQUEST, [
            'strting tracer........................' => $jaegerExporterOptions,
            'service name .....................'     => $serviceName,
        ]);
        $abcd = Tracer::start($exporter, $tracerOptions);

        Trace::info(TraceCode::TALLY_TOKEN_REQUEST, [
            'strted tracerrequest handler...............................' => $abcd->tracer()->enabled(),
        ]);
        Trace::info(TraceCode::TALLY_TOKEN_REQUEST, [
            'strted tracer...............................' => $tracerOptions,
        ]);

        //Tracer::start($exporter, $tracerOptions);

        return $next($request);
    }

    private function getSpanOptions($request, $routeName)
    {
        $attrs = Tracing::getBasicSpanAttributes($this->app);

        $attrs[TracingConstant::SPAN_KIND] = TracingConstant::SERVER;

        $attrs[TracingConstant::HTTP . '.' . TracingConstant::URL] = URL::current();

        $route = $request->route();

        if (!empty($route[2]))
        {
            $requestAttributes = $route[2];
        }
        else
        {
            $requestAttributes = $this->fetchLoggableBody($request);
        }

        $spanName = $this->getSpanName($attrs[TracingConstant::HTTP . '.' . TracingConstant::URL]);

        $spanOptions = [TracingConstant::NAME => $spanName, TracingConstant::ATTRIBUTES => $attrs];

        $routeParamPrefix = TracingConstant::HTTP . '.' . TracingConstant::ROUTE_PARAMS;

        if (empty($requestAttributes) === false)
        {
            foreach ($requestAttributes as $key => $value)
            {
                $spanOptions[TracingConstant::ATTRIBUTES][$routeParamPrefix . $key] = $value;
            }
        }

        $spanOptions[TracingConstant::ATTRIBUTES]['route_name'] = $routeName;

        return $spanOptions;
    }

    private function getSpanName($url)
    {
        $urlInfo = parse_url($url);

        $spanName = 'root';

        if (key_exists('path', $urlInfo))
        {
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

        }

        return $spanName;
    }

    private function fetchRouteName(Request $request)
    {
        $route = $request->route();

        if (empty($route[1]['as']) === true)
        {
            return 'other';
        }

        return $route[1]['as'];
    }

    private function fetchLoggableBody(Request $request): array
    {
        $loggableBody     = [];
        $loggableBodyKeys = ['client_id', 'grant_type', 'merchant_id', 'mode', 'application_id'];
        $content          = json_decode($request->getContent(), true);
        if (!empty($content))
        {
            foreach ($content as $key => $value)
            {
                if (in_array($key, $loggableBodyKeys, true))
                {
                    $loggableBody[$key] = $value;
                }
            }
        }

        return $loggableBody;
    }
}
