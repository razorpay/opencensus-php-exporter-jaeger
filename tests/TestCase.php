<?php

namespace App\Tests;

use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\TestCase as LumenTestCase;

use Illuminate\Database\Eloquent\Factory;

class TestCase extends LumenTestCase
{
    use DatabaseTransactions;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost:8000';

    /**
     * @var array
     */
    protected $testData;

    /**
     * @var string|null
     */
    protected $testDataFilePath = null;

    public function setUp()
    {
        parent::setUp();

        $factoryPath = __DIR__ . '/../vendor/razorpay/oauth/database/factories';

        $this->app->make(Factory::class)->load($factoryPath);

        $this->loadTestData();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        return $app;
    }

    protected function loadTestData()
    {
        static $testData = null;

        if (($this->testDataFilePath !== null) and
            ($testData === null))
        {
            $testData = require $this->testDataFilePath;
        }

        $this->testData = $testData;
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
