<?php

namespace App\Tests\Functional\ClientController;

use App\Tests\Concerns\RequestResponseFlowTrait;
use App\Tests\TestCase as TestCase;
use Razorpay\OAuth\Application;
use Razorpay\OAuth\Client;

class ClientTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $application;

    protected $prodClient;

    protected $devClient;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/ClientTestData.php';

        parent::setup();

        $this->setInternalAuth('rzp', env('APP_API_SECRET'));
    }

    public function testRefreshClients()
    {
        $this->createTestApp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients';

        $data['request']['content']['application_id'] = $this->application->getId();

        $response = $this->startTest($data);

        $newClients = $response['client_details'];

        $this->assertNotEquals( $this->devClient["id"],$newClients["dev"]["id"]);

        $this->assertNotEquals($this->prodClient["id"],$newClients["prod"]["id"]);
    }

    public function testCreateClients()
    {
        $this->createTestApp();

        $data = &$this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients';

        $data['request']['content']['application_id'] = $this->application->getId();

        $response = $this->runRequestResponseFlow($data);

        $newClients = $response['client_details'];

        $this->assertValidClient($newClients['dev']);

        $this->assertValidClient($newClients['prod']);
    }

    public function testCreateClientsWithNoMerchant()
    {
        $this->createTestApp();

        $data = &$this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients';

        $data['request']['content']['application_id'] = $this->application->getId();

        $this->startTest($data);
    }

    public function testCreateClientsWithWrongMerchantId()
    {
        $this->createTestApp();

        $data = &$this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients';

        $data['request']['content']['application_id'] = $this->application->getId();

        $this->startTest($data);
    }

    public function testDeleteClients()
    {
        $this->createTestApp();

        $data = &$this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients/'.$this->devClient->id;

        $response = $this->runRequestResponseFlow($data);

        $this->assertEquals([],$response);
    }

    public function testDeleteNoRecordClients()
    {
        $data = &$this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients/dummy_client';

        $this->runRequestResponseFlow($data);
    }

    public function testDeleteClientsWithNoMerchant()
    {
        $this->createTestApp();

        $data = &$this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients/dummy_client';

        $this->startTest($data);
    }

    public function testRefreshClientsInvalidInput()
    {
        $this->createTestApp();

        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/clients';

        $this->startTest($data);
    }

    public function createTestApp(array $appData = null)
    {
        if ($appData === null)
        {
            $appData = ['name' => 'apptest', 'website' => 'https://www.example.com', 'type' => 'partner'];
        }

        $this->application = factory(Application\Entity::class)->create($appData);

        $this->prodClient = factory(Client\Entity::class)->create(['application_id' => $this->application->getId(), 'environment' => 'prod']);

        $this->devClient = factory(Client\Entity::class)->create(['application_id' => $this->application->getId(), 'environment' => 'dev']);
    }

    protected function assertValidClient(array $content)
    {
        $this->assertArrayHasKey('id', $content);
        $this->assertArrayHasKey('merchant_id', $content);
        $this->assertArrayHasKey('secret', $content);
        $this->assertArrayHasKey('type', $content);
        $this->assertArrayHasKey('application_id', $content);
        $this->assertArrayHasKey('environment', $content);
        $this->assertArrayHasKey('type', $content);
    }

}
