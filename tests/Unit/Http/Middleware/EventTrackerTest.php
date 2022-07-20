<?php

namespace Unit\Http\Middleware;

use App\Constants\TraceCode;
use App\Http\Middleware\EventTracker;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Illuminate\Http\Request;
use Mockery;
use Razorpay\Trace\Facades\Trace;

class EventTrackerTest extends UnitTestCase
{
    private $segmentAnalyticsClientMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setSegmentAnalyticsClientMock(Mockery::mock('overload:App\Services\Segment\SegmentAnalyticsClient'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return mixed
     */
    public function getSegmentAnalyticsClientMock()
    {
        return $this->segmentAnalyticsClientMock;
    }

    /**
     * @param mixed $segmentAnalyticsClientMock
     */
    public function setSegmentAnalyticsClientMock($segmentAnalyticsClientMock)
    {
        $this->segmentAnalyticsClientMock = $segmentAnalyticsClientMock;
    }

    /**
     * @Test
     * eventTrackerHandle should pass the request as it is pre controller layer.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testEventTrackerHandle()
    {
        $request = new Request();

        $middleware = new EventTracker();
        $response = $middleware->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });

        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * eventTrackerTerminate should send event to SegmentAnalytics post request termination.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testEventTrackerTerminate()
    {
        $this->getSegmentAnalyticsClientMock()
            ->shouldReceive('buildRequestAndSend')
            ->once();

        $middleware = new EventTracker();
        $middleware->terminate([], []);
    }

    /**
     * @Test
     * eventTrackerTerminateWithException should through error and registered in trace.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testEventTrackerTerminateWithException()
    {
        $error = new Exception('dummy_error');

        $this->getSegmentAnalyticsClientMock()
            ->shouldReceive('buildRequestAndSend')
            ->once()
            ->andThrow($error);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH_FAILURE, [
                'class' => get_class($error),
                'code' => $error->getCode(),
                'message' => $error->getMessage(),
            ]])
            ->once();

        $middleware = new EventTracker();
        $middleware->terminate([], []);
    }
}
