<?php

namespace App\Constants;

class Tracing
{
    const SERVICE_NAME_IN_JAEGER = 'auth-service';
    const SPAN_KIND              = 'span.kind';
    const SERVER                 = 'server';
    const CLIENT                 = 'client';
    const QUERY                  = 'query';
    const ATTRIBUTES             = 'attributes';
    const HTTP                   = 'http';
    const URL                    = 'url';
    const NAME                   = 'name';
    const KIND                   = 'kind';
    const ROUTE_PARAMS           = 'route.params.';
    const STATUS_CODE            = 'status_code';
    const ID_LENGTH              = 14;
}
