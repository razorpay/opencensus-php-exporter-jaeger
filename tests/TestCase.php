<?php

namespace App\Tests;

// use Tests\Concerns\CustomAssertions;
use DB;
use Illuminate\Support\Facades\Artisan;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\TestCase as LumenTestCase;

class TestCase extends LumenTestCase
{
    use DatabaseTransactions;
    // use CustomAssertions;
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost:8000';
    protected $testDataFilePath = null;

    public function setUp()
    {
        parent::setUp();
        $this->loadTestData();
        $this->prepareForTests();
        DB::beginTransaction();
    }
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        // $app->make(\Illuminate\Foundation\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function prepareForTests()
    {
        Artisan::call('migrate');
    }

    protected function loadTestData()
    {
        static $testData = null;
        if (($this->testDataFilePath !== null) and
            ($testData === null))
        {
            $testData = require($this->testDataFilePath);
        }
        $this->testData = $testData;
    }

    public function tearDown()
    {
        DB::rollBack();

        Artisan::call('migrate:reset');

        parent::tearDown();
    }
}
