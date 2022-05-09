<?php


namespace App\Services\DataLake;


use App;
use Trace;
use App\Constants\TraceCode;
use App\Services\DataLake\Event\Event;
use App\Services\Kafka\Producer\KafkaProducer;

class DEEventsKafkaProducer
{
    private $event;

    private $app;

    public function __construct(Event $event)
    {
        $this->app = App::getFacadeRoot();

        $this->event = $event;
    }

    public function trackEvent()
    {
        if ($this->app->runningUnitTests() === true)
        {
            return;
        }

        $payload = $this->event->getEventPayload();

        $group = $this->event->getEventGroup();

        try
        {
            (new KafkaProducer(
                $this->getKafkaTopicName($group, $payload),
                json_encode($payload))
            )->Produce();
        } catch (\Throwable $e)
        {
            Trace::info(TraceCode::DE_EVENT_PUSH_FAILURE, ['message' => 'failed to send data to data lake']);
        }
    }

    private function getKafkaTopicName(string $eventGroup, array $eventPayload)
    {
        $properties = [
            $eventGroup,
            $eventPayload['event_type'],
            $eventPayload['version'],
            $eventPayload['mode'] ?? 'live'
        ];

        return join(".", $properties);
    }
}
