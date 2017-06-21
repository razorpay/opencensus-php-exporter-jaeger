<?php

namespace App\Tests\Functional;

use Request;

use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

class OAuthTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setup()
    {
        $this->testDataFilePath = __DIR__ . '/OAuthTestData.php';

        parent::setup();
    }

    public function testGetAuthCode()
    {
        $data = $this->testData[__FUNCTION__];

        // Not sure what we're testing here, this route returns a view
        // $content = ($this->runRequestResponseFlow($data))->getContent();
    }

    public function testPostAuthCode()
    {
        $data = $this->testData[__FUNCTION__];

        $application = factory(Application\Entity::class)->create();

        factory(Client\Entity::class)->create(['application_id' => $application->id, 'environment' => 'dev']);
        $prodClient = factory(Client\Entity::class)->create(
            [
                'application_id' => $application->id,
                'redirect_url' => ['https://www.example.com'],
                'environment' => 'prod'
            ]);

        $data['request']['content']['client_id'] = $prodClient->id;

        $content = ($this->runRequestResponseFlow($data))->getContent();

        $content = urldecode($content);

        $pos = strpos($content, 'code=');

        $code = substr($content, $pos + 5);

        $this->getAccessTokenTest($code, $prodClient->id);
    }

    private function getAccessTokenTest($authCode, $clientId)
    {
        Request::clearResolvedInstances();

        $data = $this->testData[__FUNCTION__];

        $data['request']['content']['code'] = $authCode;

        $data['request']['content']['client_id'] = $clientId;

        $client = (new Client\Entity)->findOrFail($clientId);

        $data['request']['content']['client_secret'] = $client->getDecryptedSecret();

        $content = ($this->sendRequest($data['request']))->getContent();

        $content = json_decode($content, true);

        $this->assertArrayHasKey('token_type', $content);

        $this->assertArrayHasKey('access_token', $content);

        $this->assertArrayHasKey('refresh_token', $content);
    }
}
