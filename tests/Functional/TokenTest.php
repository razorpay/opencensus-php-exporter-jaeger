<?php

namespace App\Tests\Functional;

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

    public function testRevokeByPartner()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        //getting access token by calling /token
        $data1['request']['url'] = '/token';

        $data1['request']['method'] = 'POST';

        $params1 = [
            'client_id'    => '30000000000000',
            'grant_type'   => 'authorization_code',
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $data1['request']['content'] = $params1;

        $response = $this->sendRequest($data1['request']);
       // print_r(json_decode($content));
       // array_filter($content);

        print_r($response->getContent());

        //calling revoke by partner api
        $data3 = & $this->testData[__FUNCTION__];

        $data3['request']['url'] = '/revoke';

        $params = [
            'client_secret'   => $this->devClient->getSecret(),
            'token'           => $response->getContent()['access_token'],
            'token_type_hint' => 'access_token'
        ];

        $this->addRequestParameters($data3['request']['content'], $params);

        array_filter($content);
        print_r($data3);

        $response = $this->sendRequest($data3['request']);

        print_r($response);


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
