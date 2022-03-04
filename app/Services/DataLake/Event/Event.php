<?php


namespace App\Services\DataLake\Event;


use App;

class Event
{
    protected $name;

    protected $type;

    protected $version = 'v1';

    protected $properties;

    protected $request;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        $this->request = $this->app['request'];
    }

    public function getEventPayload()
    {
        return [
            'event_name'          => $this->name,
            'event_type'          => $this->type,
            'version'             => $this->version,
            'source'              => 'auth',
            'event_timestamp_raw' => (int)(microtime(true) * 1000000),
            'event_timestamp'     => time(),
            'producer_timestamp'  => time(),
            'properties'          => $this->properties,
            'mode'                => 'live',
        ];
    }

    public function getEventGroup()
    {
        return 'events';
    }
}
