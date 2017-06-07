<?php

namespace App\Tests\Functional;

use Crypt;
use Request;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

class OAuthTest extends TestCase
{
    // use DatabaseMigrations;
    use RequestResponseFlowTrait;

    public function setup()
    {
        $this->testDataFilePath = __DIR__ . '/OAuthTestData.php';

        parent::setup();

        // $this->fixture = factory(\Razorpay\OAuth\Application\Entity::class);
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

        $application = (new Application\Service)->createApplication(['name' => 'Auth Test Merchant',
                                                                    'merchant_id' => '10AuthMerchant',
                                                                    'website' => 'https://www.example.com']);

        $clients[0]['redirect_url'] = ['https://www.example.com'];

        $clients[0]['id'] = $application['clients']['dev']['id'];

        $clients[1]['redirect_url'] = ['https://www.example.com'];

        $clients[1]['id'] = $application['clients']['prod']['id'];

        (new Application\Service)->update($application['id'], ['clients' => $clients, 'merchant_id' => '10AuthMerchant']);

        $data['request']['content']['client_id'] = $clients[1]['id'];

        $content = ($this->runRequestResponseFlow($data))->getContent();

        $content = urldecode($content);

        $pos = strpos($content, 'code=');

        $code = substr($content, $pos + 5);

        $this->getAccessTokenTest($code, $clients[1]['id']);
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
