<?php

namespace App\Tests\Functional;

use Razorpay\OAuth\Token;
use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

class TokenTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $token;

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
}
