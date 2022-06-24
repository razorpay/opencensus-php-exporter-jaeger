<?php

namespace App\Constants;

class Metric
{
    const HTTP_REQUESTS_TOTAL                = 'http_requests_total';
    const HTTP_REQUEST_DURATION_MILLISECONDS = 'http_request_duration_milliseconds.histogram';
    const HTTP_REQUEST_EDGE_IDENTIFIER       = 'http_request_edge_identifier.histogram';
    const LABEL_METHOD                       = 'method';
    const LABEL_ROUTE                        = 'route';
    const LABEL_STATUS                       = 'status';
    const LABEL_DEFAULT_VALUE                = 'other';

    const REFRESH_CLIENTS_SUCCESS_COUNT      = 'refresh_clients_success_count';

    const REFRESH_CLIENTS_FAILURE_COUNT      = 'refresh_clients_failure_count';
}
