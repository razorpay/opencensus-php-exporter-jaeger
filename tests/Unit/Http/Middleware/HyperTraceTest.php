<?php

namespace Unit\Http\Middleware;

use App\Constants\TraceCode;
use App\Http\Middleware\HyperTracer;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Mockery;
use Razorpay\Trace\Facades\Trace;


class HyperTraceTest extends UnitTestCase
{
    private $tracingMock;
    private $requestMock;

    function setUp(): void
    {
        parent::setUp();
        $this->setTracingMock(Mockery::mock('overload:App\Trace\Hypertrace\Tracing'));
        $this->setRequestMock(Mockery::spy('overload:Illuminate\Http\Request'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return mixed
     */
    public function getRequestMock()
    {
        return $this->requestMock;
    }

    /**
     * @param mixed $requestMock
     */
    public function setRequestMock($requestMock)
    {
        $this->requestMock = $requestMock;
    }

    /**
     * @return mixed
     */
    public function getTracingMock()
    {
        return $this->tracingMock;
    }

    /**
     * @param mixed $tracingMock
     */
    public function setTracingMock($tracingMock)
    {
        $this->tracingMock = $tracingMock;
    }

    /**
     * @return void
     */
    public function mockInstrumentation()
    {
        Mockery::mock('alias:OpenCensus\Trace\Integrations\Mysql')
            ->shouldReceive('load')
            ->once();

        Mockery::mock('alias:OpenCensus\Trace\Integrations\Curl')
            ->shouldReceive('load')
            ->once();

        Mockery::mock('alias:OpenCensus\Trace\Integrations\PDO')
            ->shouldReceive('load')
            ->once();

        Mockery::mock('alias:OpenCensus\Trace\Tracer')
            ->shouldReceive('start')
            ->once();
    }

    /**
     * @Test
     * hyperTraceIsNotEnabled should pass the request and not trace if not enabled for this app.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testHyperTraceIsNotEnabled()
    {
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::JAEGER_INFO, [
                'jaeger_app_enabled' => false,
            ]])
            ->once();

        $this->getTracingMock()
            ->shouldReceive('isEnabled')
            ->once()
            ->andReturn(false);

        $request = new Request();

        $middleware = new HyperTracer();
        $response = $middleware->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });

        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * routeTraceEnabledWithRoute should pass the request and trace for this app but not for route trace.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testRouteTraceEnabledWithRoute()
    {
        $this->getTracingMock()
            ->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $this->getTracingMock()
            ->shouldReceive('shouldTraceRoute')
            ->once()
            ->with('dummy_route_name')
            ->andReturn(false);

        $this->getRequestMock()
            ->shouldReceive('route')
            ->andReturn([
                [],
                ['as' => 'dummy_route_name']
            ]);

        Trace::shouldReceive('info')
            ->withArgs([
                TraceCode::JAEGER_INFO,
                ['jaeger_app_route' => false, 'route_name' => 'dummy_route_name']
            ])
            ->once();

        $request = new Request();

        $middleware = new HyperTracer();
        $response = $middleware->handle($request, function ($req) {
        });

        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * routeTraceEnabledWithoutRoute should pass the request and trace for this app but not for route trace.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testRouteTraceEnabledWithoutRoute()
    {
        $this->getTracingMock()->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $this->getTracingMock()->shouldReceive('shouldTraceRoute')
            ->once()
            ->with('other')
            ->andReturn(false);

        $this->getRequestMock()
            ->shouldReceive('route')
            ->andReturn([
                [],
                ['as' => '']
            ]);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::JAEGER_INFO, [
                'jaeger_app_route' => false,
                'route_name' => 'other',
            ]])
            ->once();

        $request = new Request();

        $middleware = new HyperTracer();
        $response = $middleware->handle($request, function ($req) {
        });

        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * routeTraceEnabledWithLoggableBody should pass the request and route trace with attributes from request body.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testRouteTraceEnabledWithLoggableBody()
    {
        $this->mockInstrumentation();

        $this->getTracingMock()
            ->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $this->getTracingMock()
            ->shouldReceive('getBasicSpanAttributes')
            ->between(2, 2)
            ->andReturn([]);

        $this->getRequestMock()
            ->shouldReceive('route')
            ->andReturn([[], ['as' => 'dummy_route_name']])
            ->shouldReceive('getContent')
            ->andReturn('{"client_id":"client_123","merchant_id":"merchant_123"}');

        $this->getTracingMock()
            ->shouldReceive('shouldTraceRoute')
            ->once()
            ->with('dummy_route_name')
            ->andReturn(true)
            ->shouldReceive('getServiceName')
            ->once()
            ->andReturn('dummy_service_name');

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::JAEGER_INFO, [
                'jaeger_app_route' => true,
                'route_name' => 'dummy_route_name',
            ]])
            ->once();

        Config::shouldReceive('get')
            ->with('jaeger.host')
            ->andReturn('dummy_jaeger_host')
            ->shouldReceive('get')
            ->with('jaeger.port')
            ->andReturn('dummy_jaeger_port');
        Config::partialMock();

        $request = new Request();

        $middleware = new HyperTracer();
        $response = $middleware->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });

        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * routeTraceEnabledWithAttributesInRoute should pass the request and route trace with attributes from route itself.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testRouteTraceEnabledWithAttributesInRoute()
    {
        $this->mockInstrumentation();

        $this->getTracingMock()
            ->shouldReceive('isEnabled')
            ->once()
            ->andReturn(true);

        $this->getRequestMock()
            ->shouldReceive('route')
            ->andReturn([
                [],
                ['as' => 'dummy_route_name'],
                ['client_id' => 'client_123', 'merchant_id' => 'merchant_123']
            ]);

        $this->getTracingMock()
            ->shouldReceive('getBasicSpanAttributes')
            ->between(2, 2)
            ->andReturn([]);

        $this->getTracingMock()
            ->shouldReceive('shouldTraceRoute')
            ->once()
            ->with('dummy_route_name')
            ->andReturn(true)
            ->shouldReceive('getServiceName')
            ->once()
            ->andReturn('dummy_service_name');

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::JAEGER_INFO, [
                'jaeger_app_route' => true,
                'route_name' => 'dummy_route_name',
            ]])
            ->once();

        Config::shouldReceive('get')
            ->with('jaeger.host')
            ->andReturn('dummy_jaeger_host')
            ->shouldReceive('get')
            ->with('jaeger.port')
            ->andReturn('dummy_jaeger_port');
        Config::partialMock();

        $request = new Request();

        $middleware = new HyperTracer();
        $response = $middleware->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });

        $this->assertEquals(null, $response);
    }

}
