<?php


namespace App\Services\DataLake\Event;


class OnBoardingEvent extends Event
{
    const EVENT_TYPE   = 'onboarding-events';

    const EVENT_VERSION = 'v1';

    public function __construct(string $eventName, array $customProperties)
    {
        parent::__construct();

        $this->name = $eventName;

        $this->type = self::EVENT_TYPE;

        $this->version = self::EVENT_VERSION;

        $this->properties = $customProperties;
    }

    public function getEventGroup()
    {
        return 'lumberjack';
    }
}
