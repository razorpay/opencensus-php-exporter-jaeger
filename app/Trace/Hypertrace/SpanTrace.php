<?php

namespace App\Trace\Hypertrace;

use App\Constants\TraceCode;
use OpenCensus\Trace\Span;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use OpenCensus\Trace\Tracer as OpenCensusTracer;

class SpanTrace
{
    public static function inSpan(array $spanOptions, callable $callable, array $arguments = [])
    {
        return OpenCensusTracer::inSpan($spanOptions, $callable, $arguments);
    }

    public static function startSpan(array $spanOptions = [])
    {
        try
        {
            return OpenCensusTracer::startSpan($spanOptions);
        }
        catch (\Throwable $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                                  ['startSpan', $e->getMessage()]);
        }
    }

    public static function withSpan(Span $span)
    {
        try
        {
            return OpenCensusTracer::withSpan($span);
        }
        catch (\Throwable $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                                  ['withSpan', $e->getMessage()]);
        }
    }

    public static function injectContext(ArrayHeaders $headers)
    {
        try
        {
            return OpenCensusTracer::injectContext($headers);
        }
        catch (\Throwable $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                                  ['injectContext', $e->getMessage()]);
        }
    }

    public static function spanContext()
    {
        try
        {
            return OpenCensusTracer::spanContext();
        }
        catch (\Throwable $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                                  ['spanContext', $e->getMessage()]);
        }
    }

    public static function addAttribute($attribute, $value, array $options = [])
    {
        try
        {
            if((!is_null($attribute)) and (!is_null($value))){
                return OpenCensusTracer::addAttribute($attribute, $value, $options);
            }
        }
        catch (\Throwable $e)
        {
            app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                                  ['addAttribute', $e->getMessage()]);
        }
    }

    public static function addAttributes(array $context, array $options = [])
    {
        foreach ($context as $key => $value)
        {
            try
            {
                if(is_array($value))
                {
                    $value = implode(', ', $value);
                }
                self::addAttribute($key, $value, $options);
            }
            catch (\Throwable $t)
            {
                app('trace')->warning(TraceCode::OPENCENSUS_ERROR,
                                      ['addAttributes', $t->getMessage()]);
            }
        }
    }
}
