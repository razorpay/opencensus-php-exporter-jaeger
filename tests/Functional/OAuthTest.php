<?php

namespace App\Tests\Functional;

use Crypt;
use Request;
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

        $this->fixture = factory(\Razorpay\OAuth\Application\Entity::class);
    }

    public function testGetAuthCode()
    {
        $data = $this->testData[__FUNCTION__];

        $content = ($this->runRequestResponseFlow($data))->getContent();
    }

    public function testPostAuthCode()
    {
        $data = $this->testData['testPostAuthCode'];

        // $client = $this->fixture->create(['merchant_id' => '10AuthMerchant',
        //                                   'name' => 'authTest',
        //                                   'redirect_url' => 'https://www.example.com',
        //                                   'type' => 'public',
        //                                   'secret' => Crypt::encrypt('supersecuresecret')]);

        $application = $this->fixture->create(['name' => 'Auth Test Merchant',
                                               'merchant_id' => '10AuthMerchant',
                                               'website' => 'https://www.example.com']);

        $application = Application\Entity::with('clients')->firstOrFail();

        $clients = $application->clients()->get();

        $data['request']['content']['client_id'] = $client->id;

        $content = ($this->runRequestResponseFlow($data))->getContent();

        $content = urldecode($content);

        $pos = strpos($content, 'code=');

        $code = substr($content, $pos + 5);

        $this->testGetAccessToken($code, $client->id);
    }

    public function testGetAccessToken($authCode, $clientId)
    {
        Request::clearResolvedInstances();

        $data = $this->testData['testGetAccessToken'];

        $data['request']['content']['code'] = $authCode;

        $data['request']['content']['client_id'] = $clientId;

        $content = ($this->sendRequest($data['request']))->getContent();

        $content = json_decode($content, true);

        $this->assertArrayHasKey('token_type', $content);

        $this->assertArrayHasKey('access_token', $content);

        $this->assertArrayHasKey('refresh_token', $content);
    }
}