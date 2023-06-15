<?php

namespace App\Services;

use App;
use Trace;
use Request;
use App\Request\Requests;
use App\Constants\Metric;
use App\Constants\TraceCode;
use App\Constants\RequestParams;
use http\Exception\RuntimeException;

class SplitzService
{
    const CONTENT_TYPE_JSON = 'application/json';

    const EVALUATE_URL      = 'twirp/rzp.splitz.evaluate.v1.EvaluateAPI/Evaluate';

    protected $app;

    protected $baseUrl;

    protected $key;

    protected $secret;

    protected $enabled;

    protected $timeout;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();
        $config = app('config')['trace.services.splitz'];
        $this->baseUrl = $config['url'];
        $this->key     = $config['username'];
        $this->secret  = $config['secret'];
        $this->enabled = $config['enabled'];
        $this->timeout = $config['request_timeout'];
    }

    /**
     * Checks whether splitz experiment is enabled for provided MID
     * @param array $properties Requires experiment_id and id attributes
     * @param string $checkVariant variant name configured in the splitz experiment
     * @return bool
     */
    public function isExperimentEnabled(array $properties, string $checkVariant = 'enable') : bool
    {
        $response = $this->app['splitz']->evaluateRequest($properties);

        $variant = $response['response']['variant']['name'] ?? null;

        $result =  $variant == $checkVariant;

        Trace::info(TraceCode::SPLITZ_REQUEST_RESPONSE, [
            'properties' => $properties,
            'result'     => $result,
        ]);

        return $result;
    }

    /**
     * Sends a request to splitz to evaluate the experiment for provided MID
     * @param array $input Requires experiment_id and id attributes
     * @return array
     */
    public function evaluateRequest(array $input) : array
    {
        if (!empty($input) && $this->enabled)
        {
            return $this->sendRequest($input, self::EVALUATE_URL);
        }

        return [];
    }

    /**
     * Sends a request to splitz and returns the parsed response
     * @param array $parameters Requires experiment_id and id attributes
     * @param string $path Request path
     * @return array
     */
    private function sendRequest(array $parameters, string $path) : array
    {
        $start = millitime();
        $success = false;

        $requestParams = $this->getRequestParams($parameters, $path);

        try
        {
            $response = $this->makeRequest($requestParams);

            $result = $this->parseAndReturnResponse($response);

            $success = true;

            return $result;
        }
        catch (\Throwable $e)
        {
            Trace::error(TraceCode::SPLITZ_INTEGRATION_ERROR, [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        finally
        {
            $durationInMs = millitime() - $start;

            app('trace')->histogram(Metric::SPLITZ_REQUEST_DURATION_MILLISECONDS, $durationInMs, [
                Metric::LABEL_STATUS => $success
            ]);
        }
        return [];
    }

    /**
     * Sends a request to splitz service
     * @param array $requestParams
     * @return array http response
     */
    private function makeRequest(array $requestParams)
    {
        Trace::info(TraceCode::SPLITZ_REQUEST, [
            'url'  => $requestParams['url'],
            'data' => $requestParams['data']
        ]);

        return Requests::post(
            $requestParams['url'],
            $requestParams['headers'],
            $requestParams['data'],
            $requestParams['options']);
    }

    /**
     * Fetches the request params for the splitz request to be made
     * @param array $parameters
     * @param string $path Request path
     * @return array
     */
    private function getRequestParams(array $parameters, string $path) : array
    {
        $url = $this->baseUrl . $path;

        $headers = [];

        $parameters = json_encode($parameters);

        $headers['Content-Type'] = self::CONTENT_TYPE_JSON;

        if (!empty(Request::header(RequestParams::DEV_SERVE_USER)))
        {
            $headers[RequestParams::DEV_SERVE_USER] = Request::header(RequestParams::DEV_SERVE_USER);
        }

        $options = [
            'timeout' => $this->timeout,
            'auth'    => [$this->key, $this->secret],
        ];

        return [
            'url'     => $url,
            'headers' => $headers,
            'data'    => $parameters,
            'options' => $options
        ];
    }

    /**
     * Parses the response received from splitz
     * @param mixed $res http response
     * @return array Returns status_code and response
     */
    private function parseAndReturnResponse($res) : array
    {
        $code = $res->status_code;

        app('trace')->count(Metric::SPLITZ_REQUESTS_TOTAL, [
            Metric::LABEL_STATUS =>  $code
        ]);

        $res = json_decode($res->body, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            throw new RuntimeException("Malformed json response");
        }

        if ($code >= 400)
        {
            Trace::error(TraceCode::SPLITZ_REQUEST_FAILED, [
                'status_code' => $code,
                'response'    => $res
            ]);
        }

        return ['status_code' => $code, 'response' => $res];
    }
}
