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
use Illuminate\Support\Facades\Route;
use OpenCensus\Trace\Integrations\PDO;
use OpenCensus\Trace\Integrations\Curl;
use OpenCensus\Trace\Integrations\Mysql;
use App\Constants\Tracing as TracingConstant;
use OpenCensus\Trace\Exporter\JaegerExporter;
use OpenCensus\Trace\Propagator\JaegerPropagator;
use function Webmozart\Assert\Tests\StaticAnalysis\null;

class HyperTracer
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $app = App::getFacadeRoot();
        if (Tracing::isEnabled($app) === false)
        {
            \Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_enabled' => false,
            ]);

            return $next($request);
        }
        ////$routeName = $this->fetchRouteName($request);
        ////$route = $request->route();
        //\Trace::info(TraceCode::TALLY_AUTHORIZE_REQUEST, [
        //    'route snammmmeemmbcdhzkjd' => $this->fetchRouteName($request),
        //]);

        if (Tracing::shouldTraceRoute($routeName) === false)
        {
            \Trace::info(TraceCode::JAEGER_INFO, [
                'jaeger_app_route' => false,
                'route_name'       => $routeName,
            ]);

            return $next($request);
        }
        \Trace::info(TraceCode::JAEGER_INFO, [
            'jaeger_app_route' => true,
            'route_name'       => $routeName,
        ]);
        // Load all useful extensions
        Mysql::load();
        Curl::load();
        PDO::load();

        $attrs                             = Tracing::getBasicSpanAttributes($app);
        $attrs[TracingConstant::SPAN_KIND] = TracingConstant::SERVER;

        $spanOptions = $this->getSpanOptions($request, $routeName, $app);

        $propagator    = new JaegerPropagator();
        $tracerOptions = [
            'propagator'        => $propagator,
            'root_span_options' => $spanOptions];

        $serviceName = Tracing::getServiceName($app);

        $jaegerExporterOptions = [
            'host' => $app['config']->get('jaeger.host'),
            'port' => $app['config']->get('jaeger.port')
        ];

        $exporter = new JaegerExporter($serviceName, $jaegerExporterOptions);
        \Trace::info(TraceCode::DELETE_APPLICATION_REQUEST, $tracerOptions);
        Tracer::start($exporter, $tracerOptions);

        return $next($request);
    }

    private function getSpanOptions($request, $routeName, $app)
    {
        $attrs = Tracing::getBasicSpanAttributes($app);

        $attrs[TracingConstant::SPAN_KIND] = TracingConstant::SERVER;

        $attrs[TracingConstant::HTTP . '.' . TracingConstant::URL] = URL::current();

        $route = $request->route();

        $parameterMap = null;

        if ( $route and $route->hasParameters() ){
            $parameterMap = $route->parameters;
        }

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

        $spanOptions[TracingConstant::ATTRIBUTES]['route_name'] = $routeName;

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

    private function fetchRouteName(Request $request)
    {
        $route = $request->route();
        //Trace::info( TraceCode::TALLY_TOKEN_REQUEST, [
        //    "route here"                => $request->route(),
        //]);
        //Trace::info( TraceCode::TALLY_TOKEN_REQUEST, [
        //    "request here"                => $request,
        //]);

        if (empty($route[1]['as']) === true) {
            return 'other';
        }

        return $route[1]['as'];
    }
}
