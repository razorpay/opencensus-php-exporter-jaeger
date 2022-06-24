<?php

namespace App\Tests\Functional;

use Razorpay\OAuth\Application;
use Razorpay\OAuth\Client;
use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

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

}
