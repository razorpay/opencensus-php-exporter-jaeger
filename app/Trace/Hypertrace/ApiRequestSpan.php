<?php

namespace App\Trace\Hypertrace;

use Trace;
use App\Constants\Tracing;
use WpOrg\Requests\Requests;
use App\Constants\TraceCode;
use OpenCensus\Trace\Propagator\ArrayHeaders;

class ApiRequestSpan
{

    public static function getRequestSpanOptions(string $url): array
    {
        $urlInfo = parse_url($url);

        $name = $urlInfo['host'];

        if (array_key_exists('path', $urlInfo))
        {
            $name = $name . $urlInfo['path'];
        }

        $spanOptions = [
            Tracing::NAME             => $name,
            'kind'                    => Tracing::CLIENT,
            'sameProcessAsParentSpan' => false
        ];

        $attrs = [Tracing::SPAN_KIND => Tracing::CLIENT];

        if (array_key_exists(Tracing::QUERY, $urlInfo))
        {

            parse_str($urlInfo[Tracing::QUERY], $queryParams);

            $attrs += $queryParams;
        }

        $spanOptions[Tracing::ATTRIBUTES] = $attrs;

        return $spanOptions;
    }


    // wraps the external API call being done in the trace span
    /**
     * @param $methodName
     * @param $methodArgs
     * @param $defaultSpanOptions
     *
     * @return mixed
     * @throws \Throwable
     */
    private static function wrapRequestInSpan($methodName, $methodArgs, $defaultSpanOptions=array())
    {
        $response = null;
        $span = SpanTrace::startSpan($defaultSpanOptions);
        $scope = SpanTrace::withSpan($span);

        // inject spanContext into trace propagation headers
        $headers = [];
        if(count($methodArgs) > 1)
        {
            $headers = $methodArgs[1];
        }

        $arrHeaders = new ArrayHeaders($headers);
        SpanTrace::injectContext($arrHeaders);
        $headers = $arrHeaders->toArray();
        $methodArgs[1] = $headers;

        $methodTag = ($methodName == 'request') ? $methodArgs[3] : $methodName;
        $span->addAttribute('http.method', $methodTag);

        try
        {
            $response = Requests::$methodName(...$methodArgs);
        }
        catch(\Throwable $e)
        {
            $span->addAttribute('error', 'true');
            Trace::info(TraceCode::JAEGER_API_CALL_FAIL, [
                'message'   => $e->getMessage(),
                'code'      => $e->getCode(),
            ]);
            throw $e;
        }
        finally{
            $scope->close();
        }

        if (!is_null($response))
        {
            // add response status as a span tags
            $statusCode = $response->status_code;
            $span->addAttribute('http.status_code', $statusCode);
            if ($statusCode >= 400)
            {
                $span->addAttribute('error', 'true');
            }
        }

        return $response;
    }
}
