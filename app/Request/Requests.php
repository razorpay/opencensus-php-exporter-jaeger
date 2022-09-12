<?php

namespace App\Request;

use WpOrg\Requests\Requests as Req;

/**
 * Wraps Requests Library methods to add
 * Tracing Spans and other common functionalities for
 * external API calls.
 */
class Requests
{
    /**
     * POST method
     *
     * @var string
     */
    const POST = Req::POST;

    /**
     * PUT method
     *
     * @var string
     */
    const PUT = Req::PUT;

    /**
     * GET method
     *
     * @var string
     */
    const GET = Req::GET;

    /**
     * HEAD method
     *
     * @var string
     */
    const HEAD = Req::HEAD;

    /**
     * DELETE method
     *
     * @var string
     */
    const DELETE = Req::DELETE;

    /**
     * OPTIONS method
     *
     * @var string
     */
    const OPTIONS = Req::OPTIONS;

    /**
     * TRACE method
     *
     * @var string
     */
    const TRACE = Req::TRACE;

    /**
     * PATCH method
     *
     * @link https://tools.ietf.org/html/rfc5789
     * @var string
     */
    const PATCH = 'PATCH';


    public static function request($url, $headers = array(), $data = array(), $type = self::GET, $options = array())
    {
        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'request',
            array($url, $headers, $data, $type, $options),
            $spanOptions
        );
    }

    public static function get($url, $headers = array(), $options = array())
    {
        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'get',
            array($url, $headers, $options),
            $spanOptions
        );
    }

    public static function head($url, $headers = array(), $options = array())
    {
        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'head',
            array($url, $headers, $options),
            $spanOptions
        );
    }

    public static function delete($url, $headers = array(), $options = array())
    {
        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'delete',
            array($url, $headers, $options),
            $spanOptions
        );
    }

    public static function trace($url, $headers = array(), $options = array())
    {
        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'trace',
            array($url, $headers, $options),
            $spanOptions
        );
    }

    public static function post($url, $headers = array(), $data = array(), $options = array())
    {
        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'post',
            array($url, $headers, $data, $options),
            $spanOptions
        );
    }

    public static function put($url, $headers = array(), $data = array(), $options = array())
    {
        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'put',
            array($url, $headers, $data, $options),
            $spanOptions
        );
    }

    public static function options($url, $headers = array(), $data = array(), $options = array())
    {

        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'options',
            array($url, $headers, $data, $options),
            $spanOptions
        );
    }

    public static function patch($url, $headers, $data = array(), $options = array())
    {

        $spanOptions = ApiRequestSpan::getRequestSpanOptions($url);

        return ApiRequestSpan::wrapRequestInSpan(
            'patch',
            array($url, $headers, $data, $options),
            $spanOptions
        );
    }

}
