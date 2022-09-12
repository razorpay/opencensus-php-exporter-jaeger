<?php

namespace Unit\Service\Segment;

use App\Constants\TraceCode;
use App\Services\Segment\SegmentAnalyticsClient;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Illuminate\Support\Facades\App;
use Mockery;
use Razorpay\Trace\Facades\Trace;
use WpOrg\Requests\Response;

class SegmentAnalyticsClientTest extends UnitTestCase
{

    private $requestMock;

    /**
     * @return Mockery\MockInterface
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

    public function setUp(): void
    {
        parent::setUp();
        $this->setRequestMock(Mockery::mock('overload:Requests'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * testPushTrackEvent should keep track fo event data.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testPushTrackEvent()
    {
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH, \Mockery::any()])
            ->once();
        $segmentAnalyticsClient = new SegmentAnalyticsClient();
        $segmentAnalyticsClient->pushTrackEvent(
            'user_id',
            [
                'event_label' => 'some_label',
                'merchant_id' => 'some_merchant'
            ],
            'client_id'
        );
    }

    /**
     * @Test
     * testPushTrackEvent should handle exception and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testPushTrackEventWhenExceptionThrown()
    {
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH, \Mockery::any()])
            ->once()
            ->andThrow(new Exception('some_error'));

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH_FAILURE, [
                'type' => 'track',
                'merchant_id' => 'some_merchant',
                'message' => 'some_error'
            ]])
            ->once();

        $segmentAnalyticsClient = new SegmentAnalyticsClient();
        $segmentAnalyticsClient->pushTrackEvent(
            'some_user_id',
            [
                'event_label' => 'some_label',
                'merchant_id' => 'some_merchant'
            ],
            'some_event_name'
        );
    }

    /**
     * @Test
     * hyperTraceIsNotEnabled should pass the request and not trace if not enabled for this app.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testBuildRequestAndSend()
    {
        $expectedResponse = new Response();
        $expectedResponse->status_code = 200;

        App::shouldReceive('runningUnitTests')
            ->andReturn(false);
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH, \Mockery::any()])
            ->once();
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH_SUCCESS, \Mockery::any()])
            ->once();
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);
        $segmentAnalyticsClient = new SegmentAnalyticsClient();
        $segmentAnalyticsClient->pushTrackEvent(
            'user_id',
            [
                'event_label' => 'some_label',
                'merchant_id' => 'some_merchant'
            ],
            'client_id'
        );
        $segmentAnalyticsClient->buildRequestAndSend();
    }

    /**
     * @Test
     * hyperTraceIsNotEnabled should pass the request and not trace if not enabled for this app.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testBuildRequestAndSendOnException()
    {
        $expectedResponse = new Response();
        $expectedResponse->status_code = 200;

        App::shouldReceive('runningUnitTests')
            ->andReturn(false);
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH, \Mockery::any()])
            ->once();
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH_SUCCESS, \Mockery::any()])
            ->never();
        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::SEGMENT_EVENT_PUSH_FAILURE, [
                'class' => 'App\\Services\\Segment\\SegmentAnalyticsClient',
                'message' => 'some_error',
                'type' => 'segment-analytics'
            ]])
            ->once();
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow(new Exception('some_error'));
        $segmentAnalyticsClient = new SegmentAnalyticsClient();
        $segmentAnalyticsClient->pushTrackEvent(
            'user_id',
            [
                'event_label' => 'some_label',
                'merchant_id' => 'some_merchant'
            ],
            'client_id'
        );
        $segmentAnalyticsClient->buildRequestAndSend();
    }

}
