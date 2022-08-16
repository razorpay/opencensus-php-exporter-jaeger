<?php

namespace App\Tests\Functional\TokenController;

use Trace;
use App\Constants\TraceCode;
use Request;
use Razorpay\OAuth\Application;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Token;
use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

class TokenTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $token;

    /**
     * @var Client\Entity
     */
    protected $devClient;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/TokenTestData.php';

        parent::setup();

        $this->setInternalAuth('rzp', env('APP_API_SECRET'));
    }

    public function testGetToken()
    {
        $this->createTestToken();

        $data = $this->prepareTestData();

        $data['response']['content']['id'] = $this->token->getId();

        $data['response']['content']['client_id'] = $this->token->client->getId();

        $this->startTest($data);
    }

    public function testGetMissingToken()
    {
        $this->startTest();
    }

    public function testGetAllTokens()
    {
        $this->createTestToken();

        $this->createTestToken();

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertEquals(2, $content['count']);

        $this->assertEquals('collection', $content['entity']);
    }

    public function testDeleteToken()
    {
        $this->createTestToken();

        $data = $this->prepareTestData();

        $this->startTest($data);
    }

    public function testRevokeAccessTokenByPartner()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        //getting access token by calling /token

        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'authorization_code',
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $response = $this->sendRequest($data1['request']);

        //decoding the request
        $data = json_decode($response->getContent());

        //calling revoke by partner api
        $data3 = & $this->testData[__FUNCTION__];

        $data3['request']['url'] = '/revoke';
        //adding access token to our params
        $params = [
            'client_secret'   => $this->devClient->getSecret(),
            'token'           => $data->access_token,
            'token_type_hint' => 'access_token'
        ];

        //combining the request content
        $this->addRequestParameters($data3['request']['content'], $params);

        Request::clearResolvedInstances();

        $response = $this->sendRequest($data3['request']);

        $this->assertEquals(200, $response->getStatusCode());

        Request::clearResolvedInstances();

        $data4 = [
            'request'  => [
                'method'  => 'POST',
                'url'     => '/token',
                'content' => [
                ]
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'description' => 'Authorization code has been revoked',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'   => \Razorpay\OAuth\Exception\BadRequestException::class,
                'message' => 'Authorization code has been revoked',
            ],
        ];

        $params = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'authorization_code',
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $this->addRequestParameters($data4['request']['content'], $params);

        $this->runRequestResponseFlow($data4);
    }

    public function testRevokeAccessTokenForMobileApp()
    {
        $this->generateAuthCode();

        Request::clearResolvedInstances();

        //getting access token by calling /token

        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'mobile_app_client_credentials',
            'client_secret' => $this->devClient->getSecret(),
            'user_id' => '20000000000000',
            'scope' => 'x_mobile_app'
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $this->sendRequest($data1['request']);

        //calling revoke by partner api
        $data2 = & $this->testData[__FUNCTION__];

        $data2['request']['url'] = '/revokeTokensForMobileApp';
        //adding access token to our params
        $params = [
            'client_id' => $this->devClient->getId(),
            'merchant_id' => '30000000000000',
            'user_id' => '20000000000000',
        ];

        //combining the request content
        $this->addRequestParameters($data2['request']['content'], $params);

        Request::clearResolvedInstances();

        $response = $this->sendRequest($data2['request']);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals("{\"message\":\"Token Revoked\"}", $response->getContent());

        Request::clearResolvedInstances();
    }

    public function testRevokeRefreshTokenByPartner()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        //getting refresh token by calling /token

        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'authorization_code',
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $response = $this->sendRequest($data1['request']);

        //decoding the request
        $data = json_decode($response->getContent());

        //calling revoke by partner api
        $data3 = & $this->testData[__FUNCTION__];

        $data3['request']['url'] = '/revoke';
        //adding access token to our params
        $params = [
            'client_secret'   => $this->devClient->getSecret(),
            'token'           => $data->refresh_token,
            'token_type_hint' => 'refresh_token'
        ];

        //combining the request content
        $this->addRequestParameters($data3['request']['content'], $params);

        Request::clearResolvedInstances();

        $response = $this->sendRequest($data3['request']);

        $this->assertEquals(200, $response->getStatusCode());

        Request::clearResolvedInstances();

        $data4 = [
            'request'  => [
                'method'  => 'POST',
                'url'     => '/token',
                'content' => [
                ]
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'description' => 'Token has been revoked',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'   => \Razorpay\OAuth\Exception\BadRequestException::class,
                'message' => 'Token has been revoked',
            ],
        ];

        $params = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => $data->refresh_token,
        ];

        $this->addRequestParameters($data4['request']['content'], $params);

        $this->runRequestResponseFlow($data4);
    }

    // testValidatePublicTokenWithValidToken verifies if /public_tokens/{id}/validate returns true when a valid public token is provided
    public function testValidatePublicTokenWithValidToken()
    {
        $this->createTestToken();

        $publicToken = $this->token['public_token'];
        $mode = $this->token['mode'];

        $data = $this->testData[__FUNCTION__];

        $url = $data['request']['url'];
        $url = sprintf($url, sprintf('rzp_%s_oauth_%s', $mode, $publicToken));
        $data['request']['url'] = $url;

        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertArraySelectiveEquals($data['response']['content'], $content);
    }

    // testValidatePublicTokenWithInvalidToken verifies if /public_tokens/{id}/validate returns false when an invalid public token is provided
    public function testValidatePublicTokenWithInvalidToken()
    {
        $data = $this->testData[__FUNCTION__];
        $content = $this->makeRequestAndGetContent($data['request']);
        $this->assertArraySelectiveEquals($data['response']['content'], $content);
    }


    protected function addRequestParameters(array & $content, array $parameters)
    {
        $content = array_merge($content, $parameters);
    }

    public function createTestToken(string $type = 'access_token')
    {
        $this->token = factory(Token\Entity::class)->create(['type' => $type]);
    }

    protected function prepareTestData()
    {
        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/tokens/'.$this->token->getId();

        return $data;
    }

    protected function generateAuthCode()
    {
        $this->application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $this->application->id, 'environment' => 'prod']);

        $this->devClient = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data = $this->testData['testPostAuthCode'];

        $data['request']['content']['client_id'] = $this->devClient->id;

        $response = ($this->sendRequest($data['request']))->getContent();

        $content = urldecode($response);

        $pos = strpos($content, 'code=');
        $end = strpos($content, '\'" />', $pos);

        return substr($content, $pos + 5, $end - $pos - 5);
    }
}
