<?php

namespace App\Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\TestCase as LumenTestCase;

class TestCase extends LumenTestCase
{
    use DatabaseTransactions;
    use DatabaseMigrations;

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

    /**
     * Run the database migrations for the application.
     *
     * Overrides function in Laravel\Lumen\Testing\DatabaseMigrations
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('rzp:migrate');

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
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
        parent::tearDown();
    }
}
