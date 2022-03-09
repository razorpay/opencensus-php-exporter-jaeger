<?php

namespace App\Services\Segment;

use App;
use Requests;
use Trace;
use Carbon\Carbon;
use App\Constants\TraceCode;
use App\Constants\RequestParams;

class SegmentAnalyticsClient
{
    const TRACK_EVENT_URL_PATTERN = '/v1/batch';

    const SEGMENT_EVENT_CATEGORY = "Backend - offline - Segment";

    const TIME_ZONE = 'Asia/Kolkata';

    protected $app;

    protected $urlPattern;

    protected $config = [];

    protected $events = [];

    const REQUEST_TIMEOUT = 20;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->urlPattern = self::TRACK_EVENT_URL_PATTERN;

        $this->config = app('config')['trace.services.segment_analytics'];
    }

    public function pushTrackEvent(string $userId, array $properties, string $eventName)
    {
        try
        {
            $eventProperties = $this->getEventProperties($eventName, $properties);

            $eventData = [
                'type'                  => 'track',
                'properties'            => $eventProperties,
                'event'                 => $eventName,
                'timestamp'             => Carbon::now(self::TIME_ZONE)->format('d-m-Y H:i:s')
            ];

            if (empty($userId) === true)
            {
                $eventData['anonymousId'] = $properties['merchant_id'];
            }
            else {
                $eventData['userId'] = $userId;
            }

            $this->pushEvent($eventData);
        }
        catch (\Exception $e)
        {
            Trace::critical(TraceCode::SEGMENT_EVENT_PUSH_FAILURE,
                [
                    'type'        => 'track',
                    'merchant_id' => $properties['merchant_id'],
                    'message'     => $e->getMessage()
                ]);
        }
    }

    private function pushEvent(array $eventData)
    {
        Trace::info(TraceCode::SEGMENT_EVENT_PUSH,
            [
                'event_data' => $eventData
            ]
        );

        $this->events[] = $eventData;
    }

    public function buildRequestAndSend()
    {
        try
        {
            if ($this->app->runningUnitTests() === true or empty($this->events) === true)
            {
                return;
            }

            $writeKey = $this->config['auth']['write_key'];

            $headers = [
                'content-type'  => RequestParams::CONTENT_TYPE,
                'Authorization' => 'Basic '. base64_encode($writeKey . ':' . ''),
            ];

            $url = $this->config['url'] . $this->urlPattern;

            $payload = [
                'batch' => $this->events
            ];

            $this->sendEventRequest($headers, $url, $payload);

            Trace::info(TraceCode::SEGMENT_EVENT_PUSH_SUCCESS, ['payload' => $payload]);

            $this->flushEvents();
        }
        catch (\Exception $e)
        {
            $errorContext = [
                'class'     => get_class($this),
                'message'   => $e->getMessage(),
                'type'      => 'segment-analytics'
            ];

            Trace::critical(TraceCode::SEGMENT_EVENT_PUSH_FAILURE, $errorContext);
        }
    }

    private function sendEventRequest(array $headers, string $url, array $eventData)
    {
        $response =  Requests::post($url, $headers, json_encode($eventData), ['timeout' => self::REQUEST_TIMEOUT]);

        return [
            'status_code' => $response->status_code,
            'body'        => json_decode($response->body, true)
        ];
    }

    private function flushEvents()
    {
        $this->events = [];
    }

    private function getEventProperties(string $eventName, array $properties)
    {
        $properties += [
            'event_category' => self::SEGMENT_EVENT_CATEGORY,
            'event_action'   => $eventName
        ];

        $eventLabel = EventCode::EVENT_LABELS[$eventName] ?? "";

        if (empty($eventLabel) === false)
        {
            $properties['event_label'] = $eventLabel;
        }

        return $properties;
    }
}
