<?php

namespace App\Tests\Functional;

use DB;
use Trace;
use Crypt;
use Request;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\OAuthServer;
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

    public function setup()
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
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertArrayHasKey('access_token', $content);
        $this->assertArrayHasKey('refresh_token', $content);
        $this->assertArrayHasKey('expires_in', $content);
    }

    public function testPostAccessTokenWithInvalidGrant()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

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
        $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret()
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectSecret()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

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
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

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
        $end = strpos($content, '" />', $pos);

        return substr($content, $pos + 5, $end - $pos - 5);
    }

    protected function addRequestParameters(array & $content, array $parameters)
    {
        $content = array_merge($content, $parameters);
    }
}
