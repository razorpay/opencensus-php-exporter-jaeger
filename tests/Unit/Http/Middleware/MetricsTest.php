<?php

namespace Unit\Http\Middleware;

use App\Constants\Metric;
use App\Http\Middleware\Metrics;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Http\Request;
use Mockery;
use Razorpay\Trace\Facades\Trace;
use Symfony\Component\HttpFoundation\Response;


class MetricsTest extends UnitTestCase
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
     * @Test
     * pushHttpMetricsWithRoute should push HTTP Metrics with route defined in request.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testPushHttpMetricsWithRoute()
    {
        $this->getRequestMock()
            ->shouldReceive('getMethod')
            ->andReturn('GET')
            ->shouldReceive('route')
            ->andReturn([
                [],
                ['as' => 'dummy_route_name']
            ]);

        Trace::shouldReceive('count')
            ->withArgs([Metric::HTTP_REQUESTS_TOTAL, [
                Metric::LABEL_METHOD => 'GET',
                Metric::LABEL_ROUTE => 'dummy_route_name',
                Metric::LABEL_STATUS => 200,
            ]])
            ->once()
            ->shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_DURATION_MILLISECONDS, Mockery::any(), [
                Metric::LABEL_METHOD => 'GET',
                Metric::LABEL_ROUTE => 'dummy_route_name',
                Metric::LABEL_STATUS => 200,
            ]])
            ->once();

        $request = new Request();

        $middleware = new Metrics();
        $expectedResponse = Response::create("['message' => 'success']");
        $response = $middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });

        $this->assertEquals($expectedResponse->getContent(), $response->getContent());
    }

    /**
     * @Test
     * pushHttpMetricsWithEmptyRoute should push HTTP Metrics with route defined as 'other' in request.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testPushHttpMetricsWithEmptyRoute()
    {
        $this->getRequestMock()
            ->shouldReceive('getMethod')
            ->andReturn('GET')
            ->shouldReceive('route')
            ->andReturn([
                [],
                ['as' => '']
            ]);

        Trace::shouldReceive('count')
            ->withArgs([Metric::HTTP_REQUESTS_TOTAL, [
                Metric::LABEL_METHOD => 'GET',
                Metric::LABEL_ROUTE => 'other',
                Metric::LABEL_STATUS => 200,
            ]])
            ->once()
            ->shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_DURATION_MILLISECONDS, Mockery::any(), [
                Metric::LABEL_METHOD => 'GET',
                Metric::LABEL_ROUTE => 'other',
                Metric::LABEL_STATUS => 200,
            ]])
            ->once();

        $request = new Request();

        $middleware = new Metrics();
        $expectedResponse = Response::create("['message' => 'success']");
        $response = $middleware->handle($request, function ($req) use ($expectedResponse) {
            return $expectedResponse;
        });

        $this->assertEquals($expectedResponse->getContent(), $response->getContent());
    }

}
