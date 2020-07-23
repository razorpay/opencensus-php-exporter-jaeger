<?php

namespace App\Constants;

class Metric
{
    const HTTP_REQUESTS_TOTAL                = 'http_requests_total';
    const HTTP_REQUEST_DURATION_MILLISECONDS = 'http_request_duration_milliseconds.histogram';
    const LABEL_METHOD                       = 'method';
    const LABEL_ROUTE                        = 'route';
    const LABEL_STATUS                       = 'status';
    const LABEL_DEFAULT_VALUE                = 'other';
}
