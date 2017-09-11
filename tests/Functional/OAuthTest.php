<?php

namespace App\Tests\Functional;

use DB;
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

    public function testPostAuthCode()
    {
        $data = &$this->testData[__FUNCTION__];

        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'prod']);

        $devClient = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data['request']['content']['client_id'] = $devClient->id;

        $content = $this->sendRequest($data['request']);

        $content = urldecode($content->getContent());

        $this->assertContains('http://localhost?code=', $content);
    }

    public function testPostAuthCodeWithWrongResponseType()
    {
        $data = &$this->testData[__FUNCTION__];

        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'prod']);

        $devClient = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data['request']['content']['client_id'] = $devClient->id;

        $content = $this->startTest();
    }

    public function testPostAuthCodeWithReject()
    {
        $data = &$this->testData[__FUNCTION__];

        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'prod']);

        $devClient = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data['request']['content']['client_id'] = $devClient->id;

        $content = $this->startTest();
    }

    public function testPostAccessToken()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = &$this->testData[__FUNCTION__];

        $client = $this->devClient;

        $data['request']['content']['client_id'] = $client->id;

        $data['request']['content']['client_secret'] = $client->getSecret();

        $data['request']['content']['code'] = $authCode;

        $content = $this->runRequestResponseFlow($data);

        $this->assertArrayHasKey('access_token', $content);

        $this->assertArrayHasKey('refresh_token', $content);

        $this->assertArrayHasKey('expires_in', $content);
    }

    public function testPostAccessTokenWithInvalidGrant()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = &$this->testData[__FUNCTION__];

        $client = $this->devClient;

        $content = $data['request']['content'];

        $content['code'] = $authCode;

        $content['client_id'] = $client->id;

        $content['client_secret'] = $client->getSecret();

        $data['request']['content'] = $content;

        $this->startTest();
    }

    public function testPostAccessTokenWithMissingCode()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = &$this->testData[__FUNCTION__];

        $client = $this->devClient;

        $content = $data['request']['content'];

        $content['client_id'] = $client->id;

        $content['client_secret'] = $client->getSecret();

        $data['request']['content'] = $content;

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectSecret()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = &$this->testData[__FUNCTION__];

        $client = $this->devClient;

        $content = $data['request']['content'];

        $content['code'] = $authCode;

        $content['client_id'] = $client->id;

        $content['client_secret'] = 'wrongSecret';

        $data['request']['content'] = $content;

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectClientId()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = &$this->testData[__FUNCTION__];

        $client = $this->devClient;

        $content = $data['request']['content'];

        $content['code'] = $authCode;

        $content['client_id'] = 'clientId';

        $content['client_secret'] = $client->getSecret();

        $data['request']['content'] = $content;

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

        $content = ($this->sendRequest($data['request']))->getContent();

        $content = urldecode($content);

        $pos = strpos($content, 'code=');

        $end = strpos($content, '" />', $pos);

        return substr($content, $pos + 5, $end - $pos - 5);
    }
}
