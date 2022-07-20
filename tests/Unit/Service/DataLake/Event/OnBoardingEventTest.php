<?php

namespace Unit\Service\DataLake\Event;

use App\Services\DataLake\Event\OnBoardingEvent;
use App\Services\DataLake\EventCode as DataLakeEventCode;
use App\Tests\Unit\UnitTestCase;


class OnBoardingEventTest extends UnitTestCase
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
     * testGetEventGroup should return groupEvent Name ('lumberjack').
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetEventGroup()
    {
        $event = ['merchant_id' => 'some_merchant_id', 'result' => 'failure'];
        $onBoardingEvent = new OnBoardingEvent(DataLakeEventCode::OAUTH_MULTI_TOKEN_AUTH_CODE_GENERATE, $event);
        $this->assertEquals('lumberjack', $onBoardingEvent->getEventGroup());
    }

}
