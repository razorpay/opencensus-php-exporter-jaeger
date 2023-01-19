<?php

namespace App\Services;

use App\Models\Auth\Constant;
use App\Constants\RequestParams;
use Exception;
use Razorpay\Trace\Logger;
use Trace;
use Request;
use App\Request\Requests;

use App\Constants\Metric;
use App\Constants\TraceCode;
use App\Exception\LogicException;
use App\Exception\NotFoundException;

use Razorpay\OAuth\Token\Mode;

class EdgeService
{
    protected $apiUrl;

    protected $secret;

    protected $headers;

    protected $defaultOptions;

    protected bool $isPostgres; // Used only while emitting failure metric

    public function __construct($app, $edgeUrl, $edgeSecret, $isPostgres = false)
    {
        $this->apiUrl = $edgeUrl;

        $this->secret  = $edgeSecret;

        $this->headers = ['apikey' => $this->secret, 'Content-Type' => 'application/json', RequestParams::DEV_SERVE_USER => Request::header(RequestParams::DEV_SERVE_USER)];

        $this->defaultOptions = ['timeout' => env('EDGE_TIMEOUT',5) ];

        $this->isPostgres = $isPostgres;
    }

    /**
     * @throws LogicException
     * @throws NotFoundException
     */
    public function postPublicIdToEdge(array $payload)
    {
        $start = millitime();
        $success = false;
        $merchantId = $payload[Constant::MID];

        try
        {
            $postPayload = [
                'kid'        => $payload[Constant::PUBLIC_TOKEN],
                'jti'        => $payload[Constant::IDENTIFIER],
                'user_id'    => $payload[Constant::USER_ID],
                'tags'       => $this->getTags($payload[Constant::MODE]),
                'ttl'        => $payload[Constant::TTL],
            ];
            $this->createIdentifier($merchantId, $postPayload);
            $success = true;
        }
        catch(NotFoundException)
        {
            // If Identifier creation failed, try creating the consumer first and then identifier
            try
            {
                $this->createConsumer($merchantId);

                $this->createIdentifier($merchantId, $postPayload);
                $success = true;
            }
            catch (Exception $ex)
            {
                $traceCode = $this->isPostgres ? TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_POSTGRES_FAILED : TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_CASSANDRA_FAILED;
                app("trace")->traceException($ex, Logger::ERROR, $traceCode);
                throw $ex;
            }
        }
        catch(Exception $ex)
        {
            $traceCode = $this->isPostgres ? TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_POSTGRES_FAILED : TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_CASSANDRA_FAILED;
            app("trace")->traceException($ex, Logger::ERROR, $traceCode);
            throw $ex;
        }
        finally
        {
            $duration = millitime() - $start;
            app('trace')->histogram(Metric::HTTP_REQUEST_EDGE_IDENTIFIER, $duration, [
                Metric::LABEL_STATUS => $success,
            ]);
        }
    }

    private function createIdentifier(string $merchantId, array $payload)
    {
        $url = $this->apiUrl . '/consumers/' . $merchantId . '/identifier';

        Trace::info(TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE,
            [
                'merchant_id'   => $merchantId,
                'request_body'  => $payload,
            ]);

        $response = Requests::post($url, $this->headers, json_encode($payload), $this->defaultOptions);

        if($response->status_code === 404)
        {
            throw new NotFoundException("consumer is not present.");
        }

        if ($response->success === false and $response->status_code !== 409)
        {
            throw new LogicException(
                "Could not create identifier in edge",
                [
                    "response_body" => $response->body,
                    "http_status" => $response->status_code,
                ]);
        }
    }

    private function getTags(string $mode): array
    {
        $tags = ["r~oauth.public"];

        if ($mode === Mode::TEST)
        {
            $tags[] = "m~t";
        }
        else if ($mode === Mode::LIVE)
        {
            $tags[] = "m~l";
        }
        return $tags;
    }

    private function createConsumer(string $merchantId)
    {
        $url = $this->apiUrl . '/consumers';

        $postPayload = [
            'username'      =>      $merchantId
        ];

        Trace::info(TraceCode::CREATE_CONSUMER_IN_EDGE,
            [
                'merchant_id'   => $merchantId,
                'request_body'  => $postPayload,
            ]);

        $response = Requests::post($url, $this->headers, json_encode($postPayload), $this->defaultOptions);

        if ($response->success === false and $response->status_code !== 409)
        {
            throw new LogicException(
                "Could not create consumer in edge",
                [
                    "response_body" => $response->body,
                    "http_status" => $response->status_code,
                ]);
        }
    }

    /**
     * @param string $clientId
     * @return void
     * @throws LogicException
     * @throws NotFoundException
     */
    public function getOauth2Client(string $clientId): void
    {
        $url = $this->apiUrl . '/oauth2/' . $clientId;

        Trace::info(TraceCode::GET_OAUTH_CLIENT_FROM_EDGE,
            [
                'client_id' => $clientId,
            ]);

        $response = Requests::get($url, $this->headers, $this->defaultOptions);

        if ($response->status_code === 404) {
            throw new NotFoundException("oauth2 client is not present.");
        }

        if ($response->success === false and $response->status_code !== 409) {
            throw new LogicException(
                "Request to find oauth2 client in edge failed",
                [
                    "response_body" => $response->body,
                    "http_status" => $response->status_code,
                ]);
        }

    }
}
