<?php

namespace App\Trace\Hypertrace;

use App\Constants\TraceCode;
use App\Constants\Tracing as Constant;

class Tracing
{
    // all routes which are to be excluded from distributed tracing
    public static function getRoutesToExclude(): array
    {
        return [];
    }

    // all routes which are to be included from distributed tracing
    public static function getRoutesToInclude(): array
    {
        $routesToInclude = [
            'get_root',
            'get_status',
            'get_auth_code',
            'post_auth_code',
            'delete_auth_code',
            'post_access_token',
            'create_application',
            'get_application',
            'delete_application',
            'update_application',
            'create_application_clients',
            'delete_application_client',
            'get_token',
            'validate_public_token',
            'get_multiple_tokens',
            'delete_token',
            'create_token_partner',
            'create_partner_token',
            'get_admin_entities',
            'post_native_auth_code',
            'create_native_token',
        ];

        return $routesToInclude;
    }

    public static function getServiceName($app): string
    {
        $app_mode = config('jaeger.app_mode');

        if ($app_mode)
        {
            return Constant::SERVICE_NAME_IN_JAEGER . '-' . $app_mode;
        }
        else
        {
            return Constant::SERVICE_NAME_IN_JAEGER;
        }
    }

    public static function getBasicSpanAttributes($app): array
    {
        $attrs = ['service.version' => config('jaeger.tag_service_version')] ?? '1.0';

        if (isset($app['request']))
        {
            $attrs['task_id'] = app('request')->requestId ?? "123";
        }

        $app_env = config('jaeger.tag_app_env');
        if ($app_env)
        {
            $attrs['app_env'] = $app_env;
        }

        $app_mode = config('jaeger.app_mode');
        if ($app_mode)
        {
            $attrs['app_mode'] = $app_mode;
        }

        return $attrs;
    }

    public static function shouldTraceRoute($routeName): bool
    {

        if (!(in_array($routeName, self::getRoutesToInclude(), true)) or
            in_array($routeName, self::getRoutesToExclude(), true))
        {
            return false;
        }

        return true;
    }

    public static function isEnabled($app): bool
    {
        if (config('jaeger.enabled') === true)
        {
            return true;
        }

        return false;
    }
}
