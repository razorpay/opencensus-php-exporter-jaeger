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

    public function testPostAuthCode()
    {
        $data = & $this->testData[__FUNCTION__];

        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'dev']);

        $prodClient = factory(Client\Entity::class)->create(
            [
                'application_id' => $application->id,
                'redirect_url' => ['https://www.example.com'],
                'environment' => 'prod'
            ]);

        $data['request']['content']['client_id'] = $prodClient->id;

        $content = $this->sendRequest($data['request']);

        $content = urldecode($content->getContent());

        $this->assertStringStartsWith('"https:', $content);

        $this->assertContains('code=', $content);
    }

    public function testPostAuthCodeWithReject()
    {
        $data = & $this->testData[__FUNCTION__];

        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'dev']);

        $prodClient = factory(Client\Entity::class)->create(
            [
                'application_id' => $application->id,
                'redirect_url' => ['https://www.example.com'],
                'environment' => 'prod'
            ]);

        $data['request']['content']['client_id'] = $prodClient->id;

        $content = $this->startTest();
    }

    public function testPostAccessToken()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $client = $this->prodClient;

        $data['request']['content']['client_id'] = $client->id;

        $data['request']['content']['client_secret'] = $client->getDecryptedSecret();

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

        $data = & $this->testData[__FUNCTION__];

        $client = $this->prodClient;

        $content = $data['request']['content'];

        $content['code'] = $authCode;

        $content['client_id'] = $client->id;

        $content['client_secret'] = $client->getDecryptedSecret();

        $data['request']['content'] = $content;

        $this->startTest();
    }

    public function testPostAccessTokenWithMissingCode()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $client = $this->prodClient;

        $content = $data['request']['content'];

        $content['client_id'] = $client->id;

        $content['client_secret'] = $client->getDecryptedSecret();

        $data['request']['content'] = $content;

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectSecret()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $client = $this->prodClient;

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

        $data = & $this->testData[__FUNCTION__];

        $client = $this->prodClient;

        $content = $data['request']['content'];

        $content['code'] = $authCode;

        $content['client_id'] = 'clientId';

        $content['client_secret'] = $client->getDecryptedSecret();

        $data['request']['content'] = $content;

        $this->startTest();
    }

    public function testGetTokenData()
    {
        $this->startTest();
    }

    public function testGetTokenDataWithInvalidToken()
    {
        $this->startTest();
    }

    public function testGetRoot()
    {
        $this->startTest();
    }

    protected function generateAuthCode()
    {
        $this->application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $this->application->id, 'environment' => 'dev']);

        $this->prodClient = factory(Client\Entity::class)->create(
            [
                'application_id' => $this->application->id,
                'redirect_url' => ['https://www.example.com'],
                'environment' => 'prod'
            ]);

        $data = $this->testData['testPostAuthCode'];

        $data['request']['content']['client_id'] = $this->prodClient->id;

        $content = ($this->sendRequest($data['request']))->getContent();

        $content = urldecode($content);

        $pos = strpos($content, 'code=');

        return substr($content, $pos + 5);
    }
}
