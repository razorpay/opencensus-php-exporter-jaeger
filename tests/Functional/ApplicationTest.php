<?php

namespace App\Tests\Functional;

use Razorpay\OAuth\Application;
use App\Tests\TestCase as TestCase;
use App\Tests\Concerns\RequestResponseFlowTrait;

class ApplicationTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $application;

    public function setup()
    {
        $this->testDataFilePath = __DIR__ . '/ApplicationTestData.php';

        parent::setup();

        $this->setInternalAuth('rzp', env('APP_API_SECRET'));
    }

    public function testCreateApplication()
    {
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

        $this->createTestApp($appData1);

        $this->createTestApp($appData2);

        $data = & $this->testData[__FUNCTION__];

        $content = $this->makeRequestAndGetContent($data['request']);

        $this->assertEquals(2, $content['count']);

        $this->assertEquals('collection', $content['entity']);

        $this->assertEquals('apptest1', $content['items'][0]['name']);

        $this->assertEquals('apptest2', $content['items'][1]['name']);
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

    public function createTestApp(array $appData = null)
    {
        if ($appData === null)
        {
            $appData = ['name' => 'apptest', 'website' => 'https://www.example.com'];
        }

        $this->application = factory(Application\Entity::class)->create($appData);
    }

    protected function prepareTestData()
    {
        $data = & $this->testData[__FUNCTION__];

        $data['request']['url'] = '/applications/'.$this->application->getId();

        return $data;
    }
}
