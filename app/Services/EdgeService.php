<?php

namespace App\Services;

use Trace;
use Requests;

use App\Constants\Metric;
use App\Constants\TraceCode;
use App\Exception\LogicException;
use App\Exception\NotFoundException;

use Razorpay\OAuth\Token\Mode;

class EdgeService
{
    protected $apiUrl;

    protected $headers;

    protected $defaultOptions;

    public function __construct($app)
    {
        $this->apiUrl = env('EDGE_URL');

        $secret  = env('EDGE_SECRET');

        $this->headers = ['apikey' => $secret, 'Content-Type' => 'application/json'];

        $this->defaultOptions = ['timeout' => 2];
    }

    public function postPublicIdToEdge(string $publicId, string $merchantId, int $accessTokenTTLInSeconds, string $mode)
    {
        $start = millitime();
        $success = false;

        try
        {
            $postPayload = [
                'kid'        => $publicId,
                'tags'       => $this->getTags($mode),
                'ttl'        => $accessTokenTTLInSeconds
            ];
            $this->createIdentifier($merchantId, $postPayload);
            $success = true;
        }
        catch(NotFoundException $ex)
        {
            $this->createConsumer($merchantId);

            $this->createIdentifier($merchantId, $postPayload);
            $success = true;
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
}
