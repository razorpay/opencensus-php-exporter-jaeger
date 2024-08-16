<?php

namespace App\Tests\Functional\TokenController;

use Razorpay\OAuth\Constants;
use Razorpay\OAuth\Encryption;
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

    /**
     * @var Client\Entity
     */
    protected $prodClient;

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

    public function testGettAllTokensWithRevokedClient() {
        $this->application = Application\Entity::factory()->create();
        $this->prodClient = Client\Entity::factory()->create([Client\Entity::APPLICATION_ID => $this->application->id, Client\Entity::ENVIRONMENT => 'prod']);
        $this->devClient = Client\Entity::factory()->create([Client\Entity::APPLICATION_ID => $this->application->id, Client\Entity::ENVIRONMENT => 'dev', Client\Entity::REVOKED_AT => time()]);
        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::CLIENT_ID => $this->devClient->getId()]);
        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::CLIENT_ID => $this->prodClient->getId()]);

        $data = & $this->testData['testGetAllTokens'];
        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertEquals(1, $content['count']);
        $this->assertEquals('collection', $content['entity']);
        $this->assertEquals(1, count($content['items']));
        $this->assertEquals($this->prodClient->id, $content['items'][0]['client_id']);
    }

    public function testDeleteToken()
    {
        $this->createTestToken();

        $data = $this->prepareTestData();

        $this->startTest($data);
    }

    public function testRevokeApplicationAccess()
    {
        $this->application = Application\Entity::factory()->create();
        $this->prodClient = Client\Entity::factory()->create([Client\Entity::APPLICATION_ID => $this->application->id, Client\Entity::ENVIRONMENT => 'prod']);
        $this->devClient = Client\Entity::factory()->create([Client\Entity::APPLICATION_ID => $this->application->id, Client\Entity::ENVIRONMENT => 'dev', Client\Entity::REVOKED_AT => time()]);
        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::CLIENT_ID => $this->devClient->getId()]);
        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::CLIENT_ID => $this->prodClient->getId()]);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/tokens/submerchant/revoke_for_application/'.$this->application->id;

        $this->startTest($data);
    }

    //Since the token's created_at is older then 6 months, their refresh token would have expired and hence we are ignoring those tokens
    public function testRevokeApplicationAccessWithExpiredRefreshToken()
    {
        $this->application = Application\Entity::factory()->create();
        $this->prodClient = Client\Entity::factory()->create([Client\Entity::APPLICATION_ID => $this->application->id, Client\Entity::ENVIRONMENT => 'prod']);
        $this->devClient = Client\Entity::factory()->create([Client\Entity::APPLICATION_ID => $this->application->id, Client\Entity::ENVIRONMENT => 'dev']);
        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::CLIENT_ID => $this->devClient->getId(), Token\Entity::CREATED_AT => 1562400123]);
        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::CLIENT_ID => $this->prodClient->getId(), Token\Entity::CREATED_AT => 1562400123]);

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/tokens/submerchant/revoke_for_application/'.$this->application->id;

        $this->runRequestResponseFlow($data);
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
            'client_id'    => $this->devClient->getId(),
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
            'merchant_id' => $this->devClient->getId(),
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

    public function testTokenGenerationForMobileAppInvalidGrantType()
    {
        $this->generateAuthCode();

        $this->expectException(\Razorpay\OAuth\Exception\BadRequestException::class);

        Request::clearResolvedInstances();

        // Invalid Grant Type
        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'mobile_app_client_credentials_2',
            'client_secret' => $this->devClient->getSecret(),
            'user_id' => '20000000000000',
            'scope' => 'x_mobile_app'
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $this->sendRequest($data1['request']);

        Request::clearResolvedInstances();
    }

    public function testTokenGenerationForMobileAppInvalidClientId()
    {
        $this->generateAuthCode();

        $this->expectException(\Razorpay\OAuth\Exception\BadRequestException::class);

        Request::clearResolvedInstances();

        // Invalid Client Id
        $params1 = [
            'client_id'    => '300000000000',
            'grant_type'   => 'mobile_app_client_credentials',
            'client_secret' => $this->devClient->getSecret(),
            'user_id' => '20000000000000',
            'scope' => 'x_mobile_app'
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $this->sendRequest($data1['request']);

        Request::clearResolvedInstances();
    }

    public function testTokenGenerationForMobileAppInvalidUserId()
    {
        $this->generateAuthCode();

        $this->expectException(\Razorpay\OAuth\Exception\BadRequestException::class);

        Request::clearResolvedInstances();

        // Missing User Id
        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'mobile_app_client_credentials',
            'client_secret' => $this->devClient->getSecret(),
            'scope' => 'x_mobile_app'
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $this->sendRequest($data1['request']);

        Request::clearResolvedInstances();
    }

    public function testTokenGenerationForMobileAppInvalidScope()
    {
        $this->generateAuthCode();

        $this->expectException(\Razorpay\OAuth\Exception\ServerException::class);

        Request::clearResolvedInstances();

        // Invalid scope
        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'mobile_app_client_credentials',
            'client_secret' => $this->devClient->getSecret(),
            'user_id' => '20000000000000',
            'scope' => 'x_mobile_app_2'
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $this->sendRequest($data1['request']);

        Request::clearResolvedInstances();
    }

    public function testTokenGenerationForMobileAppMissingRefreshToken()
    {
        $this->generateAuthCode();

        $this->expectException(\Razorpay\OAuth\Exception\BadRequestException::class);

        Request::clearResolvedInstances();

        // RefreshTokenMissing
        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'mobile_app_refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'user_id' => '20000000000000'
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $this->sendRequest($data1['request']);

        Request::clearResolvedInstances();
    }

    public function testTokenGenerationForMobileAppInvalidRefreshToken()
    {
        $this->generateAuthCode();

        $this->expectException(\Razorpay\OAuth\Exception\BadRequestException::class);

        Request::clearResolvedInstances();

        // Invalid refresh Token
        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'mobile_app_refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => 'refresh_token',
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $this->sendRequest($data1['request']);

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
                        'description' => 'Token is already revoked',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'   => \Razorpay\OAuth\Exception\BadRequestException::class,
                'message' => 'Token is already revoked',
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
        $this->token = Token\Entity::factory()->create(['type' => $type]);
    }

    protected function prepareTestData()
    {
        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/tokens/'.$this->token->getId();

        return $data;
    }

    protected function generateAuthCode()
    {
        $this->application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $this->application->id, 'environment' => 'prod']);

        $this->devClient = Client\Entity::factory()->create(
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

    protected function generateAuthCodeWithClientId($client_id): string
    {
        $this->application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $this->application->id, 'environment' => 'prod']);

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => $client_id,
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data = $this->testData['testPostAuthCodeWithClientId'];

        $data['request']['content']['client_id'] = $this->devClient->id;

        $response = ($this->sendRequest($data['request']))->getContent();

        $content = urldecode($response);

        $pos = strpos($content, 'code=');
        $end = strpos($content, '\'" />', $pos);

        return substr($content, $pos + 5, $end - $pos - 5);
    }

    public function testHandleRevokeTokenRequestForMobileApp()
    {
        $input = [];

        $this->expectException(\Razorpay\Spine\Exception\ValidationFailureException::class);

        $method = new \ReflectionMethod("\App\Models\Token\Service", "handleRevokeTokenRequestForMobileApp");

        $method->setAccessible(true);

        $obj = new \App\Models\Token\Service();

        $this->assertNull($method->invoke($obj, 'id123', $input));
    }

    public function testRefreshTokenGrantGeneratesAReusableRefreshToken() {

        $clientId = '27000000000000';

        $authCode = $this->generateAuthCodeWithClientId($clientId);

        Request::clearResolvedInstances();

        $params1 = [
            'client_id'    => $clientId,
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

        $firstRefreshToken = $data->refresh_token;

        $params1 = [
            'client_id'    => $clientId,
            'grant_type'   => 'refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => $firstRefreshToken,
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        Request::clearResolvedInstances();

        sleep(2);

        $response = $this->sendRequest($data1['request']);

        $data = json_decode($response->getContent());

        $secondRefreshToken = $data->refresh_token;

        $params1 = [
            'client_id'    => $clientId,
            'grant_type'   => 'refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => $secondRefreshToken,
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        Request::clearResolvedInstances();

        sleep(2);

        $response = $this->sendRequest($data1['request']);

        $this->assertEquals(200, $response->getStatusCode());

        $params1 = [
            'client_id'    => $clientId,
            'grant_type'   => 'refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => $firstRefreshToken,
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        Request::clearResolvedInstances();

        sleep(2);

        $response = $this->sendRequest($data1['request']);

        $data = json_decode($response->getContent());

        $thirdRefreshToken = $data->refresh_token ;

        $this->assertEquals(200, $response->getStatusCode());


        $firstRefreshTokenData = (new Encryption(Constants::ENTITY_REFRESH_TOKEN))->decryptWithFallback($firstRefreshToken);
        $secondRefreshTokenData = (new Encryption(Constants::ENTITY_REFRESH_TOKEN))->decryptWithFallback($secondRefreshToken);
        $thirdRefreshTokenData = (new Encryption(Constants::ENTITY_REFRESH_TOKEN))->decryptWithFallback($thirdRefreshToken);

        $rt1 = \json_decode($firstRefreshTokenData, true);
        $rt2 = \json_decode($secondRefreshTokenData, true);
        $rt3 = \json_decode($thirdRefreshTokenData, true);

        // Asserts that the refresh_token_id is same for all the tokens.
        $this->assertEquals($rt1['refresh_token_id'], $rt2['refresh_token_id']);
        $this->assertEquals($rt2['refresh_token_id'], $rt3['refresh_token_id']);

        // Asserts that the expiry time is updated for the refresh token
        $this->assertGreaterThan($rt1['expire_time'], $rt2['expire_time']);
        $this->assertGreaterThan($rt2['expire_time'], $rt3['expire_time'] );

        // Asserts that access tokens for each refresh token is different
        $this->assertNotEquals($rt1['access_token_id'], $rt2['access_token_id']);
        $this->assertNotEquals($rt2['access_token_id'], $rt3['access_token_id']);
        $this->assertNotEquals($rt3['access_token_id'], $rt1['access_token_id']);


        // Asserts that access tokens to older refresh tokens are revoked.
        $this->assertTrue((new Token\Repository())->isAccessTokenRevoked($rt1['access_token_id']));
        $this->assertTrue((new Token\Repository())->isAccessTokenRevoked($rt2['access_token_id']));
        $this->assertFalse((new Token\Repository())->isAccessTokenRevoked($rt3['access_token_id']));

    }

    public function testRefreshTokenGrantGeneratesAReusableRefreshTokenForAllBasedOnEnvVar() {

        $clientId = '30000000000000';

        putenv('USE_REUSABLE_REFRESH_TOKENS=true');

        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $params1 = [
            'client_id'    => $clientId,
            'grant_type'   => 'authorization_code',
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        sleep(2);

        $response = $this->sendRequest($data1['request']);

        //decoding the request
        $data = json_decode($response->getContent());

        $firstRefreshToken = $data->refresh_token;

        $params1 = [
            'client_id'    => $clientId,
            'grant_type'   => 'refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => $firstRefreshToken,
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        Request::clearResolvedInstances();

        sleep(2);

        $response = $this->sendRequest($data1['request']);

        $data = json_decode($response->getContent());

        $secondRefreshToken = $data->refresh_token;

        $params1 = [
            'client_id'    => $clientId,
            'grant_type'   => 'refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => $secondRefreshToken,
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        Request::clearResolvedInstances();

        sleep(2);

        $response = $this->sendRequest($data1['request']);

        $this->assertEquals(200, $response->getStatusCode());

        $params1 = [
            'client_id'    => $clientId,
            'grant_type'   => 'refresh_token',
            'client_secret' => $this->devClient->getSecret(),
            'refresh_token' => $firstRefreshToken,
        ];

        $data1['request']['content'] = $params1;

        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        Request::clearResolvedInstances();

        sleep(2);

        $response = $this->sendRequest($data1['request']);

        $data = json_decode($response->getContent());

        $thirdRefreshToken = $data->refresh_token ;

        $this->assertEquals(200, $response->getStatusCode());


        $firstRefreshTokenData = (new Encryption(Constants::ENTITY_REFRESH_TOKEN))->decryptWithFallback($firstRefreshToken);
        $secondRefreshTokenData = (new Encryption(Constants::ENTITY_REFRESH_TOKEN))->decryptWithFallback($secondRefreshToken);
        $thirdRefreshTokenData = (new Encryption(Constants::ENTITY_REFRESH_TOKEN))->decryptWithFallback($thirdRefreshToken);

        $rt1 = \json_decode($firstRefreshTokenData, true);
        $rt2 = \json_decode($secondRefreshTokenData, true);
        $rt3 = \json_decode($thirdRefreshTokenData, true);

        // Asserts that the refresh_token_id is same for all the tokens.
        $this->assertEquals($rt1['refresh_token_id'], $rt2['refresh_token_id']);
        $this->assertEquals($rt2['refresh_token_id'], $rt3['refresh_token_id']);

        // Asserts that the expiry time is updated for the refresh token
        $this->assertGreaterThan($rt1['expire_time'], $rt2['expire_time']);
        $this->assertGreaterThan($rt2['expire_time'], $rt3['expire_time'] );

        // Asserts that access tokens for each refresh token is different
        $this->assertNotEquals($rt1['access_token_id'], $rt2['access_token_id']);
        $this->assertNotEquals($rt2['access_token_id'], $rt3['access_token_id']);
        $this->assertNotEquals($rt3['access_token_id'], $rt1['access_token_id']);


        // Asserts that access tokens to older refresh tokens are revoked.
        $this->assertTrue((new Token\Repository())->isAccessTokenRevoked($rt1['access_token_id']));
        $this->assertTrue((new Token\Repository())->isAccessTokenRevoked($rt2['access_token_id']));
        $this->assertFalse((new Token\Repository())->isAccessTokenRevoked($rt3['access_token_id']));

        }
}
