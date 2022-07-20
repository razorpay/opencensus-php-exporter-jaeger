<?php

namespace Unit\Http\Middleware;

use App\Constants\TraceCode;
use App\Constants\Tracing;
use App\Tests\Unit\UnitTestCase;
use App\Trace\Hypertrace\SpanTrace;
use Exception;
use Mockery;
use OpenCensus\Trace\Propagator\ArrayHeaders;
use OpenCensus\Trace\Span;
use Razorpay\Trace\Facades\Trace;


class SpanTraceTest extends UnitTestCase
{

    const ERROR = 'start span failed';
    private $openCensusMock;
    private $spanMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->openCensusMock = Mockery::mock('overload:OpenCensus\Trace\Tracer');
        $this->spanMock = \Mockery::mock('overload:OpenCensus\Trace\Span');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * inSpan should properly delegate call to OpenCensusTracer::inSpan.
     * @return void
     */
    public function testInSpan()
    {
        $spanOptions = [];
        $callable = function () {
            // process something directly here...
        };
        $arguments = [];
        $this->openCensusMock
            ->shouldReceive('inSpan')
            ->withArgs([$spanOptions, $callable, $arguments])
            ->andReturn([]);

        SpanTrace::inSpan($spanOptions, $callable, $arguments);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * startSpan should properly delegate call to OpenCensusTracer::startSpan.
     * @return void
     */
    public function testStartSpan()
    {
        $spanOptions = [];
        $this->openCensusMock
            ->shouldReceive('startSpan')
            ->withArgs([$spanOptions])
            ->andReturn([]);

        SpanTrace::startSpan($spanOptions);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * startSpan should catch Exception and trace with warning.
     * @return void
     */
    public function testStartSpanWithError()
    {
        $spanOptions = [];
        $exception = new Exception(self::ERROR);
        Trace::shouldReceive('warning')
            ->withArgs([TraceCode::OPENCENSUS_ERROR, Mockery::any()])
            ->once();
        $this->openCensusMock
            ->shouldReceive('startSpan')
            ->withArgs([$spanOptions])
            ->andThrow($exception);

        SpanTrace::startSpan($spanOptions);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * withSpan should properly delegate call to OpenCensusTracer::withSpan.
     * @return void
     */
    public function testWithSpan()
    {
        $span = new Span();
        $this->openCensusMock
            ->shouldReceive('withSpan')
            ->withArgs([$span])
            ->andReturn([]);

        SpanTrace::withSpan($span);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * withSpan should catch Exception and trace with warning.
     * @return void
     */
    public function testWithSpanException()
    {
        $span = new Span();
        $exception = new Exception(self::ERROR);
        Trace::shouldReceive('warning')
            ->withArgs([TraceCode::OPENCENSUS_ERROR, ['withSpan', $exception->getMessage()]])
            ->once();
        $this->openCensusMock
            ->shouldReceive('withSpan')
            ->withArgs([$span])
            ->andThrow($exception);

        SpanTrace::withSpan($span);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * injectContext should properly delegate call to OpenCensusTracer::injectContext.
     * @return void
     */
    public function testInjectContext()
    {
        $headers = new ArrayHeaders([]);
        $this->openCensusMock
            ->shouldReceive('injectContext')
            ->withArgs([$headers])
            ->andReturn([]);

        SpanTrace::injectContext($headers);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * injectContext should catch Exception and trace with warning.
     * @return void
     */
    public function testInjectContextException()
    {
        $headers = new ArrayHeaders([]);
        $exception = new Exception(self::ERROR);
        Trace::shouldReceive('warning')
            ->withArgs([TraceCode::OPENCENSUS_ERROR, ['injectContext', $exception->getMessage()]])
            ->once();
        $this->openCensusMock
            ->shouldReceive('injectContext')
            ->withArgs([$headers])
            ->andThrow($exception);

        SpanTrace::injectContext($headers);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * spanContext should properly delegate call to OpenCensusTracer::spanContext.
     * @return void
     */
    public function testSpanContext()
    {
        $this->openCensusMock
            ->shouldReceive('spanContext')
            ->andReturn([]);

        SpanTrace::spanContext();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * spanContext should catch Exception and trace with warning.
     * @return void
     */
    public function testSpanContextException()
    {
        $exception = new Exception(self::ERROR);
        Trace::shouldReceive('warning')
            ->withArgs([TraceCode::OPENCENSUS_ERROR, ['spanContext', $exception->getMessage()]])
            ->once();
        $this->openCensusMock
            ->shouldReceive('spanContext')
            ->andThrow($exception);

        SpanTrace::spanContext();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * addAttribute should properly delegate call to OpenCensusTracer::addAttribute.
     * @return void
     */
    public function testAddAttribute()
    {
        $attribute = [Tracing::SPAN_KIND => Tracing::CLIENT];
        $value = 'merchant_id';
        $options = [];
        $this->openCensusMock
            ->shouldReceive('addAttribute')
            ->withArgs([$attribute, $value, $options])
            ->andReturn([]);

        SpanTrace::addAttribute($attribute, $value, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * addAttribute should catch Exception and trace with warning.
     * @return void
     */
    public function testAddAttributeException()
    {
        $attribute = [Tracing::SPAN_KIND => Tracing::CLIENT];
        $value = 'merchant_id';
        $options = [];
        $exception = new Exception(self::ERROR);
        Trace::shouldReceive('warning')
            ->withArgs([TraceCode::OPENCENSUS_ERROR, ['addAttribute', $exception->getMessage()]])
            ->once();
        $this->openCensusMock
            ->shouldReceive('addAttribute')
            ->andThrow($exception);

        SpanTrace::addAttribute($attribute, $value, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * addAttribute with Context should properly delegate call to OpenCensusTracer::addAttribute.
     * @return void
     */
    public function testAddAttributeWithContext()
    {
        $context = [
            'key1' => ['a', 'b'],
            'key2' => ['a', 'b']
        ];
        $options = [];
        $this->openCensusMock
            ->shouldReceive('addAttribute')
            ->withArgs([Mockery::any(), 'a, b', $options])
            ->andReturn([]);

        SpanTrace::addAttributes($context, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * addAttribute with Context should catch Exception and trace with warning.
     * @return void
     */
    public function testAddAttributeWithContextException()
    {
        $context = [
            'key1' => ['a', 'b'],
            'key2' => ['a', 'b']
        ];
        $options = [];
        $exception = new Exception(self::ERROR);
        Trace::shouldReceive('warning')
            ->withArgs([TraceCode::OPENCENSUS_ERROR, ['addAttribute', $exception->getMessage()]])
            ->times(2);
        $this->openCensusMock
            ->shouldReceive('addAttribute')
            ->times(2)
            ->andThrow($exception);

        SpanTrace::addAttributes($context, $options);
    }
}
