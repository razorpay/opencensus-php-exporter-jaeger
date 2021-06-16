<?php

namespace App\Tests\Functional;

use DB;
use Request;

use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use App\Tests\TestCase as TestCase;
use League\OAuth2\Server\CryptTrait;
use App\Tests\Concerns\RequestResponseFlowTrait;

class OAuthTest extends TestCase
{
    use RequestResponseFlowTrait;
    use CryptTrait;

    /**
     * @var Client\Entity
     */
    protected $devClient;

    /**
     * @var Application\Entity
     */
    protected $application;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/OAuthTestData.php';

        parent::setup();
    }

    public function testGetRoot()
    {
        $this->startTest();
    }

    public function testGetStatus()
    {
        $this->startTest();
    }

    public function testGetAuthorizeUrl()
    {
        $data = $this->testData[$this->getName()];

        $response = $this->sendRequest($data['request']);

        $expectedString = 'No records found with the given Id';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains($expectedString, $response->getContent());
    }

    public function testGetAuthorizeUrlWithClient()
    {
        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        $devClient = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data = [
            'method' => 'get',
            'url'    => '/authorize?response_type=code' .
                        '&client_id=' . $devClient->getId() .
                        '&redirect_uri=https://www.example.com' .
                        '&scope=read_only' .
                        '&state=123',
        ];

        $response = $this->sendRequest($data);

        $expectedString = 'Allow <span class="emphasis">' .
                          $application->getName() .
                          '</span> to access your <span class="emphasis merchant-name"></span> account on Razorpay?';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains($expectedString, $response->getContent());
    }

    public function testGetAuthorizeUrlNoStateParam()
    {
        $data = $this->testData[$this->getName()];

        $response = $this->sendRequest($data['request']);

        $expectedString = 'Validation failed. The state field is required.';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains($expectedString, $response->getContent());
    }

    public function testPostAuthCode()
    {
        $data = & $this->testData[__FUNCTION__];

        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $response = $this->sendRequest($data['request']);

        $content = urldecode($response->getContent());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertContains('http://localhost?code=', $content);
    }

    public function testPostAuthCodeInvalidToken()
    {
        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $this->startTest();
    }

    public function testPostAuthCodeInvalidRole()
    {
        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $this->startTest();
    }

    public function testPostAuthCodeWithReject()
    {
        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'prod']);

        factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $content = urldecode($response->getContent());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertContains('error=access_denied', $content);
    }

    public function testPostAccessToken()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertValidAccessToken($content);
    }

    /**
     * We send a redirect uri from the valid list of client uris but not
     * the one used for auth code generation.
     */
    public function testPostAccessTokenValidWrongRedirectUri()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'https://www.example.com',
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testPostAuthCodeAndGenerateAccessToken()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->createAndSetClientWithEnvironment();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'    => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertValidAccessToken($content);
    }

    public function testPostAuthCodeAndGenerateAccessTokenInvalidInput()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->createAndSetClientWithEnvironment();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'    => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testValidateTallyAuthUser()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->application = factory(Application\Entity::class)->create(
            [
                'type'          =>  'tally',
            ]
        );

        $this->devClient = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testValidateTallyAuthUserInvalidInput()
    {
//        $this->setInternalAuth('rzp', env('APP_API_SECRET'));
//
//        $this->createAndSetClientWithEnvironment();
//
//        Request::clearResolvedInstances();
//
//        $data = & $this->testData[__FUNCTION__];
//
//        $params = [
//            'client_id'         => $this->devClient->getId(),
//        ];
//
//        $this->addRequestParameters($data['request']['content'], $params);
//
//        $this->runRequestResponseFlow($data);
    }

    public function testTallyToken()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->createAndSetClientWithEnvironment();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
            'client_secret'     => $this->devClient->getSecret(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testTallyTokenInvalidInput()
    {
//        $this->setInternalAuth('rzp', env('APP_API_SECRET'));
//
//        $this->createAndSetClientWithEnvironment();
//
//        Request::clearResolvedInstances();
//
//        $data = & $this->testData[__FUNCTION__];
//
//        $params = [
//            'client_id'         => $this->devClient->getId(),
//            'client_secret'     => $this->devClient->getSecret(),
//        ];
//
//        $this->addRequestParameters($data['request']['content'], $params);
//
//        $this->runRequestResponseFlow($data);
    }

    public function testPostAccessTokenWithInvalidGrant()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAccessTokenWithMissingCode()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret()
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectSecret()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => 'wrongSecret',
            'code'          => $authCode,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectClientId()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => 'wrongSecret',
            'code'          => $authCode,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAuthCodeWithWrongResponseType()
    {
        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'prod']);

        factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $this->startTest();
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

    protected function addRequestParameters(array & $content, array $parameters)
    {
        $content = array_merge($content, $parameters);
    }

    protected function createAndSetClientWithEnvironment(string $env = 'dev')
    {
        $this->application = factory(Application\Entity::class)->create();

        $clientName = $env . 'Client';

        $this->{$clientName} = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => $env,
            ]);
    }

    protected function assertValidAccessToken(array $content)
    {
        $this->assertArrayHasKey('access_token', $content);
        $this->assertArrayHasKey('refresh_token', $content);
        $this->assertArrayHasKey('expires_in', $content);
        $this->assertArrayHasKey('public_token', $content);
    }

    protected function generateAuthCodeAndClearResolvedInstances()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        return $authCode;
    }
}
