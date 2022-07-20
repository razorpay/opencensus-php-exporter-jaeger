<?php

namespace Unit\Service\DataLake;

use App\Constants\TraceCode;
use App\Services\DataLake\DEEventsKafkaProducer;
use App\Services\DataLake\Event\OnBoardingEvent;
use App\Services\DataLake\EventCode as DataLakeEventCode;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Support\Facades\App;
use Mockery;
use Razorpay\Trace\Facades\Trace;


class DEEventsKafkaProducerTest extends UnitTestCase
{

    private $eventMock;
    private $kafkaProducerMock;

    /**
     * @return Mockery\MockInterface
     */
    public function getKafkaProducerMock()
    {
        return $this->kafkaProducerMock;
    }

    /**
     * @param mixed $kafkaProducerMock
     */
    public function setKafkaProducerMock($kafkaProducerMock)
    {
        $this->kafkaProducerMock = $kafkaProducerMock;
    }

    /**
     * @return Mockery\MockInterface
     */
    public function getEventMock()
    {
        return $this->eventMock;
    }

    /**
     * @param mixed $eventMock
     */
    public function setEventMock($eventMock)
    {
        $this->eventMock = $eventMock;
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->setEventMock(Mockery::mock('overload:App\Services\DataLake\Event\Event'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * testTrackEvent should push tracks of to kafka.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testTrackEvent()
    {
        App::shouldReceive('runningUnitTests')
            ->andReturn(false);
        $payload = [
            'event_name' => 'some_name',
            'event_type' => 'some_type',
            'version' => 'some_version',
            'source' => 'auth',
            'event_timestamp_raw' => (int)(microtime(true) * 1000000),
            'event_timestamp' => time(),
            'producer_timestamp' => time(),
            'properties' => [],
            'mode' => 'test',
        ];
        $this->getEventMock()
            ->shouldReceive('getEventPayload')
            ->once()
            ->andReturn($payload)
            ->shouldReceive('getEventGroup')
            ->andReturn('events');

        $this->setKafkaProducerMock(Mockery::mock('overload:App\Services\Kafka\Producer\KafkaProducer'));
        $this->getKafkaProducerMock()
            ->shouldReceive('Produce')
            ->once();

        $properties = ['merchant_id' => 'some_merchant_id', 'result' => 'failure'];
        $event = new OnBoardingEvent(DataLakeEventCode::OAUTH_MULTI_TOKEN_AUTH_CODE_GENERATE, $properties);
        $deEvent = new DEEventsKafkaProducer($event);
        $deEvent->trackEvent();
    }

    /**
     * @Test
     * testTrackEventWhenProducerThrowException should handle error and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testTrackEventWhenProducerThrowException()
    {
        App::shouldReceive('runningUnitTests')
            ->andReturn(false);
        $payload = [
            'event_name' => 'some_name',
            'event_type' => 'some_type',
            'version' => 'some_version',
            'source' => 'auth',
            'event_timestamp_raw' => (int)(microtime(true) * 1000000),
            'event_timestamp' => time(),
            'producer_timestamp' => time(),
            'properties' => [],
            'mode' => 'test',
        ];
        $this->getEventMock()
            ->shouldReceive('getEventPayload')
            ->once()
            ->andReturn($payload)
            ->shouldReceive('getEventGroup')
            ->andReturn('events');

        $this->setKafkaProducerMock(Mockery::mock('overload:App\Services\Kafka\Producer\KafkaProducer'));
        $this->getKafkaProducerMock()
            ->shouldReceive('Produce')
            ->once()
            ->andThrow(new \Exception('some_error'));

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::DE_EVENT_PUSH_FAILURE, ['message' => 'failed to send data to data lake']])
            ->once();

        $properties = ['merchant_id' => 'some_merchant_id', 'result' => 'failure'];
        $event = new OnBoardingEvent(DataLakeEventCode::OAUTH_MULTI_TOKEN_AUTH_CODE_GENERATE, $properties);
        $deEvent = new DEEventsKafkaProducer($event);
        $deEvent->trackEvent();
    }

    /**
     * @Test
     * testTrackEventShouldSkipWhenFunctionalTestRunning should skip when functional tests running.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testTrackEventShouldSkipWhenFunctionalTestRunning()
    {
        $payload = [
            'event_name' => 'some_name',
            'event_type' => 'some_type',
            'version' => 'some_version',
            'source' => 'auth',
            'event_timestamp_raw' => (int)(microtime(true) * 1000000),
            'event_timestamp' => time(),
            'producer_timestamp' => time(),
            'properties' => [],
            'mode' => 'test',
        ];
        $this->getEventMock()
            ->shouldReceive('getEventPayload')
            ->never()
            ->andReturn($payload)
            ->shouldReceive('getEventGroup')
            ->never()
            ->andReturn('events');

        $this->setKafkaProducerMock(Mockery::mock('overload:App\Services\Kafka\Producer\KafkaProducer'));
        $this->getKafkaProducerMock()
            ->shouldReceive('Produce')
            ->never()
            ->andThrow(new \Exception('some_error'));

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::DE_EVENT_PUSH_FAILURE, ['message' => 'failed to send data to data lake']])
            ->never();
        $properties = ['merchant_id' => 'some_merchant_id', 'result' => 'failure'];
        $event = new OnBoardingEvent(DataLakeEventCode::OAUTH_MULTI_TOKEN_AUTH_CODE_GENERATE, $properties);
        $deEvent = new DEEventsKafkaProducer($event);
        $deEvent->trackEvent();
    }
}
