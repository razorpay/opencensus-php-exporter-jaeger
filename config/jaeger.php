<?php

return array(
    'enabled'             => env('DISTRIBUTED_TRACING_ENABLED', true),
    'host'                => env('JAEGER_HOSTNAME', env('NODE_NAME')),
    'port'                => env('JAEGER_PORT', 6831),
    'app_mode'            => env('APP_MODE', ''),
    'tag_service_version' => env('GIT_COMMIT_HASH', ''),
    'tag_app_env'         => env('APP_ENV', '')
);

