<?php

namespace App\Tests\Functional;

use Razorpay\OAuth;
use Razorpay\OAuth\Base\Table;

use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

class AdminTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $application;

    protected $client;

    protected $token;

    protected $refreshToken;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/AdminTestData.php';

        parent::setup();

        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->createTestData();
    }

    public function testFetchMultipleApplication()
    {
        $this->fetchMultiple(Table::APPLICATIONS);
    }

    public function testFetchMultipleClient()
    {
        $this->fetchMultiple(Table::CLIENTS);
    }

    public function testFetchMultipleToken()
    {
        $this->fetchMultiple(Table::TOKENS);
    }

    public function testFetchMultipleRefreshToken()
    {
        $this->fetchMultiple(Table::REFRESH_TOKENS);
    }

    public function testFetchByApplicationId()
    {
        $response = $this->fetchById(Table::APPLICATIONS, $this->application['id']);

        $this->assertArraySelectiveEquals($this->application->toArrayAdmin(), $response);
    }

    public function testFetchByClientId()
    {
        $response = $this->fetchById(Table::CLIENTS, $this->client['id']);

        $this->assertArraySelectiveEquals($this->client->toArrayAdmin(), $response);
    }

    public function testFetchByTokenId()
    {
        $response = $this->fetchById(Table::TOKENS, $this->token['id']);

        $this->assertArraySelectiveEquals($this->token->toArrayAdmin(), $response);
    }

    public function testFetchByRefreshTokenId()
    {
        $response = $this->fetchById(Table::REFRESH_TOKENS, $this->refreshToken['id']);

        $this->assertArraySelectiveEquals($this->refreshToken->toArrayAdmin(), $response);
    }

    private function fetchMultiple(String $entity = null)
    {
        $data = $this->testData[__FUNCTION__];

        $url = $data['request']['url'];

        $url = sprintf($url, $entity);

        $data['request']['url'] = $url;

        $content = $this->makeRequestAndGetContent($data['request']);

        $response = $data['response'];

        $this->assertEquals($response['entity'], $content['entity']);

        $this->assertEquals($response['admin'], $content['admin']);

        $this->assertCount($response['count'], $content['items']);
    }

    private function fetchById(String $entity, String $entityId)
    {
        $data = $this->testData[__FUNCTION__];

        $url = $data['request']['url'];

        $url = sprintf($url, $entity, $entityId);

        $data['request']['url'] = $url;

        $content = $this->makeRequestAndGetContent($data['request']);

        return $content;
    }

    private function createTestData(array $tokenData = [])
    {
        $this->refreshToken = factory(OAuth\RefreshToken\Entity::class)->create($tokenData);

        $this->token = $this->refreshToken['token'];

        $this->client= $this->token['client'];

        $this->application = $this->client['application'];
    }
}
