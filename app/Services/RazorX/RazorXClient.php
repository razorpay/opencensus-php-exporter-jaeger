<?php

namespace App\Services\RazorX;

use Trace;
use WpOrg\Requests\Requests;
use App\Constants\TraceCode;
use WpOrg\Requests\Exception;
use function env;

class RazorXClient
{
    /**
     * The default case to be returned so that the old flow is taken
     * when the featureFlag is not to be applied to merchant or the
     * response from RazorX server is not return for some reason
     */

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $config;

    protected $trace;

    protected $requestTimeout;

    public function __construct()
    {
        $this->env            = env('APP_ENV');
        $this->config         = $this->getConfig();
        $this->baseUrl        = $this->config['url'];
        $this->key            = $this->config['username'];
        $this->secret         = $this->config['secret'];
        $this->requestTimeout = $this->config['request_timeout'];
        Trace::info(TraceCode::API_REQUEST, $this->config);
    }

    private function getConfig(): array
    {
        return [
            'mock'            => env('RAZORX_MOCK', false),
            'url'             => env('RAZORX_URL'),
            'username'        => RazorXConstants::RAZORX_USERNAME,
            'secret'          => env('RAZORX_SECRET'),
            'request_timeout' => env('RAZORX_REQUEST_TIMEOUT', 0.1),
        ];
    }

    public function getTreatment(string $id, string $featureFlag, string $mode, $retryCount = 0): string
    {
        $data = [
            RazorXConstants::ID              => $id,
            RazorXConstants::FEATURE_FLAG    => $featureFlag,
            RazorXConstants::ENVIRONMENT     => env('APP_ENV'),
            RazorXConstants::MODE            => $mode,
            RazorXConstants::RETRY_COUNT_KEY => $retryCount,
        ];

        return $this->sendRequest(RazorXConstants::EVALUATE_URI, Requests::GET, $data);
    }

    protected function sendRequest(
        string $url,
        string $method,
        array  $data = [])
    {
        if ($this->config['mock'] === true)
        {
            return RazorXConstants::DEFAULT_CASE;
        }

        $request = $this->getRequestParams($url, $method, $data);

        $retryCount = $data[RazorXConstants::RETRY_COUNT_KEY] ?? 0;

        return $this->makeRequestAndGetResponse($request, $retryCount, $retryCount);
    }

    protected function getRequestParams(string $url, string $method, array $data = []): array
    {
        $url = $this->baseUrl . $url;

        if (empty($data) === true)
        {
            $data = '';
        }

        $headers = [
            RazorXConstants::APPLICATION_HEADER_NAME => RazorXConstants::APPLICATION_HEADER_VALUE
        ];

        $options = [
            'connect_timeout' => $this->requestTimeout,
            'timeout'         => $this->requestTimeout,
            'auth'            => [$this->key, $this->secret],
        ];

        return [
            'url'     => $url,
            'method'  => $method,
            'headers' => $headers,
            'options' => $options,
            'content' => $data,
        ];
    }

    protected function makeRequestAndGetResponse(array $request, int $retryOriginalCount, int $retryCount): string
    {
        try
        {
            $response = Requests::request(
                $request['url'],
                $request['headers'],
                $request['content'],
                $request['method'],
                $request['options']);

            return $this->parseAndReturnResponse($response, $request);
        }
        catch (\Throwable $e)
        {
            if (($e instanceof Exception\Http) and
                ($this->checkRequestTimeout($e) === true) and
                ($retryCount > 0))
            {
                Trace::info(
                    TraceCode::RAZORX_SERVICE_RETRY,
                    [
                        'message' => $e->getMessage(),
                        'data'    => $e->getData(),
                    ]);
                $retryCount--;

                return $this->makeRequestAndGetResponse($request, $retryOriginalCount, $retryCount);
            }
            {
                unset($request['options']['auth']);
                Trace::error(
                    TraceCode::RAZORX_REQUEST_FAILED,
                    [
                        'request' => $request,
                        'retries' => $retryOriginalCount - $retryCount
                    ]);

                return RazorXConstants::DEFAULT_CASE;
            }
        }
    }

    protected function parseAndReturnResponse($res, $req = null): string
    {
        $code = $res->status_code;

        if ($code === 200)
        {
            $response = json_decode($res->body, true);

            return $response['value'] ?? RazorXConstants::DEFAULT_CASE;
        }
        else
        {
            unset($req['options']['auth']);

            Trace::error(TraceCode::RAZORX_REQUEST_FAILED, [
                'request'  => $req,
                'response' => json_decode($res->body, true),
            ]);
        }

        return RazorXConstants::DEFAULT_CASE;
    }

    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Exception\Http $e The caught requests exception
     *
     * @return boolean              true/false
     */
    protected function checkRequestTimeout(Exception\Http $e): bool
    {
        if ($e->getType() === 'curlerror')
        {
            $curlErrNo = curl_errno($e->getData());

            if ($curlErrNo === 28)
            {
                return true;
            }
        }

        return false;
    }
}
