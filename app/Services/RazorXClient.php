<?php

namespace App\Services;

//use Razorpay\Trace\Logger as Trace;
//use RZP\Http\Request\Requests;
//use RZP\Trace\TraceCode;

class RazorXClient
{
    ///**
    // * The default case to be returned so that the old flow is taken
    // * when the featureFlag is not to be applied to merchant or the
    // * response from RazorX server is not return for some reason
    // */
    //const DEFAULT_CASE      = 'control';
    //
    //const RETRY_COUNT_KEY = 'retry_count';
    //
    //// Params required for evaluator API
    //const ID                = 'id';
    //const FEATURE_FLAG      = 'feature_flag';
    //const ENVIRONMENT       = 'environment';
    //const MODE              = 'mode';
    //
    //const EVALUATE_URI      = 'evaluate';
    //
    //protected $baseUrl;
    //
    //protected $key;
    //
    //protected $secret;
    //
    //protected $config;
    //
    //protected $trace;
    //
    //protected $requestTimeout;
    //
    //public function __construct($app)
    //{
    //    $this->trace              = $app['trace'];
    //    $this->config             = $app['config']->get('applications.razorx');
    //    $this->baseUrl            = $this->config['url'];
    //    $this->key                = $this->config['username'];
    //    $this->secret             = $this->config['secret'];
    //    $this->env                = $app['env'];
    //    $this->requestTimeout     = $this->config['request_timeout'];
    //    $this->requestTimeoutBulk = $this->config['request_timeout_bulk'];
    //}
    //
    //
    //
    //public function getTreatment(string $id, string $featureFlag, string $mode, $retryCount = 0): string
    //{
    //    $data = [
    //        self::ID               => $id,
    //        self::FEATURE_FLAG     => $featureFlag,
    //        self::ENVIRONMENT      => env('APP_ENV'),
    //        self::MODE             => $mode,
    //        self::RETRY_COUNT_KEY  => $retryCount,
    //    ];
    //
    //    $variant = $this->sendRequest(self::EVALUATE_URI, Requests::GET, $data);
    //
    //    return $variant;
    //}
    //
    //protected function sendRequest(
    //    string $url,
    //    string $method,
    //    array $data = [])
    //{
    //    //if ($this->config['mock'] === true)
    //    //{
    //    //    return self::DEFAULT_CASE;
    //    //}
    //
    //    $request = $this->getRequestParams($url, $method, $data);
    //
    //    $retryCount = $data[self::RETRY_COUNT_KEY] ?? 0;
    //
    //    return $this->makeRequestAndGetResponse($request, $retryCount, $retryCount);
    //}
    //
    //protected function getRequestParams(
    //    string $url,
    //    string $method,
    //    array $data = []): array
    //{
    //    $url = $this->baseUrl . $url;
    //
    //    if (empty($data) === true)
    //    {
    //        $data = '';
    //    }
    //
    //    $headers = [];
    //
    //    $options = [
    //        'connect_timeout' => $this->requestTimeout,
    //        'timeout' => $this->requestTimeout,
    //        'auth'    => [$this->key, $this->secret],
    //    ];
    //
    //    return [
    //        'url'     => $url,
    //        'method'  => $method,
    //        'headers' => $headers,
    //        'options' => $options,
    //        'content' => $data,
    //    ];
    //}
    //
    //protected function makeRequestAndGetResponse(array $request, int $retryOriginalCount, int $retryCount)
    //{
    //    try
    //    {
    //        //$reqStartAt = microtime(true);
    //
    //        $response = Requests::request(
    //            $request['url'],
    //            $request['headers'],
    //            $request['content'],
    //            $request['method'],
    //            $request['options']);
    //
    //        return $this->parseAndReturnResponse($response, $request);
    //    }
    //    catch (\Throwable $e)
    //    {
    //        if (($e instanceof \Requests_Exception) and
    //            ($this->checkRequestTimeout($e) === true) and
    //            ($retryCount > 0))
    //        {
    //
    //            $retryCount--;
    //
    //            return  $this->makeRequestAndGetResponse($request, $retryOriginalCount, $retryCount);
    //        }
    //        {
    //            unset($request['options']['auth']);
    //
    //            return self::DEFAULT_CASE;
    //        }
    //    }
    //}
    //
    //protected function parseAndReturnResponse($res, $req = null)
    //{
    //    $code = $res->status_code;
    //
    //    if ($code === 200)
    //    {
    //        $response = json_decode($res->body, true);
    //
    //        return $response['value'] ?? self::DEFAULT_CASE;
    //    }
    //    else
    //    {
    //        unset($req['options']['auth']);
    //
    //        //$this->trace->error(TraceCode::RAZORX_REQUEST_FAILED, [
    //        //    'request'   => $req,
    //        //    'response'  => json_decode($res->body, true),
    //        //]);
    //    }
    //
    //    return self::DEFAULT_CASE;
    //}

}
