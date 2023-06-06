<?php

namespace App\Tests\Functional\ApplicationController;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

class ApplicationTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $application;

    /**
     * @var Application\Entity
     */
    private $applicationEntity;

    /**
     * @var Client\Entity
     */
    private $clientEntity;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/ApplicationTestData.php';

        parent::setup();

        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->applicationEntity = new Application\Entity();
        $this->clientEntity = new Client\Entity();
    }

    public function testCreateApplication()
    {
        $this->startTest();
    }

    public function testCreateApplicationInvalidSecret()
    {
        $this->setInternalAuth('rzp', 'dummy_secret');

        $this->startTest();
    }

    public function testCreateApplicationMissingInput()
    {
        $this->startTest();
    }

    public function testCreateApplicationInvalidInput()
    {
        $this->startTest();
    }

    public function testGetApplication()
    {
        $this->createTestApp();

        $data = $this->prepareTestData();

        $this->startTest($data);
    }

    public function testGetMissingApplication()
    {
        $this->startTest();
    }

    public function testGetApplications()
    {
        $appData1 = ['name' => 'apptest1', 'website' => 'https://www.example1.com'];

        $appData2 = ['name' => 'apptest2', 'website' => 'https://www.example2.com'];

        $appData3 = ['name' => 'apptest3', 'website' => 'https://www.example2.com', 'type' => 'partner'];

        $this->createTestApp($appData1);

        $this->createTestApp($appData2);

        $this->createTestApp($appData3);

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertEquals(2, $content['count']);

        $this->assertEquals('collection', $content['entity']);

        $this->assertEquals('apptest1', $content['items'][0]['name']);

        $this->assertEquals('apptest2', $content['items'][1]['name']);
    }

    public function testGetApplicationsByType()
    {
        $appData1 = ['name' => 'apptest1', 'website' => 'https://www.example1.com', 'type' => 'partner'];

        $appData2 = ['name' => 'apptest2', 'website' => 'https://www.example2.com'];

        $this->createTestApp($appData1);

        $this->createTestApp($appData2);

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertEquals(1, $content['count']);

        $this->assertEquals('collection', $content['entity']);

        $this->assertEquals('apptest1', $content['items'][0]['name']);
    }

    public function testUpdateApplication()
    {
        $this->createTestApp();

        $data = $this->prepareTestData();

        $this->startTest($data);
    }

    public function testUpdateApplicationInvalidInput()
    {
        $this->createTestApp();

        $data = $this->prepareTestData();

        $this->startTest($data);
    }

    public function testDeleteApplication()
    {
        $this->createTestApp();

        $data = $this->prepareTestData();

        $this->startTest($data);
    }

    public function testGetSubmerchantApplications()
    {
        $this->createTestApp(['name' => 'apptest', 'website' => 'https://www.example.com', 'logo_url' => '/logos/8f6s8096pYQw0v.png']);

        $this->client = Client\Entity::factory()->create(['application_id' => $this->application->getId(), 'environment' => 'prod']);

        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::SCOPES => ['read_only'], Token\Entity::CLIENT_ID => $this->client->getId(), Token\Entity::CREATED_AT => 1562400123]);

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $data['response']['content']['items'][0]['application_id'] = $this->application->getId();

        $this->assertEquals($data['response']['content'], $content);
    }

    public function testGetSubmerchantApplicationsWhenNoTokenExists()
    {
        $this->createTestApp(['name' => 'apptest', 'website' => 'https://www.example.com', 'logo_url' => '/logos/8f6s8096pYQw0v.png']);

        Client\Entity::factory()->create(['application_id' => $this->application->getId(), 'environment' => 'prod']);

        $data = & $this->testData['testGetSubmerchantApplications'];

        $data['response'] = [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => []
            ]
        ];

        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertEquals($data['response']['content'], $content);
    }

    public function testGetSubmerchantApplicationsWithVaryingScopesAndCreationTime()
    {
        $this->createTestApp(['name' => 'apptest', 'website' => 'https://www.example.com', 'logo_url' => '/logos/8f6s8096pYQw0v.png']);

        $this->client = Client\Entity::factory()->create(['application_id' => $this->application->getId(), 'environment' => 'prod']);

        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::SCOPES => ['read_only'], Token\Entity::CLIENT_ID => $this->client->getId(), Token\Entity::CREATED_AT => 1562400120, Token\Entity::REVOKED_AT => 1562400520]);

        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::SCOPES => ['read_write'], Token\Entity::CLIENT_ID => $this->client->getId(), Token\Entity::CREATED_AT => 1562400122]);

        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::SCOPES => ['rx_read_only', 'read_write', 'apple_watch_read_write'], Token\Entity::CLIENT_ID => $this->client->getId(), Token\Entity::CREATED_AT => 1562400124]);

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $data['response']['content']['items'][0]['application_id'] = $this->application->getId();

        $this->assertEquals($data['response']['content'], $content);
    }

    public function testGetMultipleSubmerchantApplications()
    {
        $this->createTestApp(['name' => 'apptestFirst', 'website' => 'https://www.example.com', 'logo_url' => '/logos/8f6s8096pYQw0v.png']);

        $app1 = $this->application;

        $this->createTestApp(['name' => 'apptestSecond', 'website' => 'https://www.example.com', 'logo_url' => '/logos/8f6s8096pYQw0g.png']);

        $app2 = $this->application;

        $this->clientA = Client\Entity::factory()->create(['application_id' => $app1->getId(), 'environment' => 'prod']);

        $this->clientB = Client\Entity::factory()->create(['application_id' => $app2->getId(), 'environment' => 'prod']);

        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::SCOPES => ['read_only'], Token\Entity::CLIENT_ID => $this->clientA->getId(), Token\Entity::CREATED_AT => 1562400120]);

        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::SCOPES => ['read_write'], Token\Entity::CLIENT_ID => $this->clientA->getId(), Token\Entity::CREATED_AT => 1562400122, Token\Entity::EXPIRES_AT => 1562410122]);

        Token\Entity::factory()->create([Token\Entity::TYPE => 'access_token', Token\Entity::SCOPES => ['rx_read_only', 'read_write'], Token\Entity::CLIENT_ID => $this->clientB->getId(), Token\Entity::CREATED_AT => 1562400124]);

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $data['response']['content']['items'][0]['application_id'] = $app2->getId();
        $data['response']['content']['items'][1]['application_id'] = $app1->getId();

        $this->assertEquals($data['response']['content'], $content);
    }

    //    TODO: Revert this after aggregator to reseller migration is complete (PLAT-33)
    public function testRestoreApplication()
    {
        list($appToDelete, $appToRestore1, $appToRestore2) = $this->setUpTestRestoreApplication();

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertEquals(0, count($content));

        $deletedApps = $this->fetchApplications([$appToDelete->getId()], true)->toArray();
        $deletedClients = $this->fetchClientsByAppIds([$appToDelete->getId()], true);
        $restoredApps = $this->fetchApplications([$appToRestore1->getId(), $appToRestore2->getId()])->toArray();
        $restoredClients = $this->fetchClientsByAppIds([$appToRestore1->getId(), $appToRestore2->getId()]);

        $this->assertCount(1, $deletedApps);
        $this->assertCount(2, $deletedClients);
        $this->assertCount(2, $restoredApps);
        $this->assertCount(4, $restoredClients);
    }

    public function createTestApp(array $appData = null)
    {
        $this->application = $this->createAuthApplication($appData);
    }

    private function createAuthApplication(array $appData = null)
    {
        if ($appData === null)
        {
            $appData = ['name' => 'apptest', 'website' => 'https://www.example.com'];
        }

        return Application\Entity::factory()->create($appData);
    }

    private function fetchApplications(array $appIds, $withTrashed = false)
    {
        $query = $this->applicationEntity->newQuery()->whereIn('id', $appIds);

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }
        return $query->get();
    }

    private function fetchClientsByAppIds(array $appIds, $withTrashed = false)
    {
        $query = $this->clientEntity->newQuery()->whereIn('application_id', $appIds);

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }
        return $query->get();
    }

    private function setUpTestRestoreApplication()
    {
        $appToDelete = $this->createAuthApplication([
            'id' => 'apptodelete000', 'name' => 'apptest', 'website' => 'https://www.example.com'
        ]);
        Client\Entity::factory()->create(['application_id' => $appToDelete->getId(), 'environment' => 'prod']);
        Client\Entity::factory()->create(['application_id' => $appToDelete->getId(), 'environment' => 'dev']);

        $appToRestore1 = $this->createTestApplication('apptorestore01');
        $appToRestore2 = $this->createTestApplication('apptorestore02');

        return [$appToDelete, $appToRestore1, $appToRestore2];
    }

    private function createTestApplication($id)
    {
        $app = $this->createAuthApplication([
            'id' => $id, 'name' => 'apptest',
            'website' => 'https://www.example.com', 'deleted_at' => time()
        ]);
        Client\Entity::factory()->create([
            'application_id' => $app->getId(), 'environment' => 'prod', 'revoked_at' => time()
        ]);
        Client\Entity::factory()->create([
            'application_id' => $app->getId(), 'environment' => 'dev', 'revoked_at' => time()
        ]);

        return $app;
    }

    protected function prepareTestData()
    {
        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/applications/'.$this->application->getId();

        return $data;
    }
}
