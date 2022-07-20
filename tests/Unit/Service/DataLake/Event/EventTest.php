<?php

namespace Unit\Service\DataLake\Event;

use App\Services\DataLake\Event\Event;
use App\Tests\Unit\UnitTestCase;

class EventTest extends UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * testGetEventGroup should return event name.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetEventGroup()
    {
        $event = new Event();
        $this->assertEquals('events', $event->getEventGroup());
    }

    /**
     * @Test
     * testGetEventPayload should return Event Payload.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetEventPayload()
    {
        $event = new Event();
        $this->assertValidPayload($event->getEventPayload());
    }

    protected function assertValidPayload(array $content)
    {
        $this->assertArrayHasKey('event_name', $content);
        $this->assertArrayHasKey('event_type', $content);
        $this->assertArrayHasKey('version', $content);
        $this->assertArrayHasKey('source', $content);
        $this->assertArrayHasKey('event_timestamp_raw', $content);
        $this->assertArrayHasKey('event_timestamp', $content);
        $this->assertArrayHasKey('producer_timestamp', $content);
        $this->assertArrayHasKey('properties', $content);
        $this->assertArrayHasKey('mode', $content);
    }
}
