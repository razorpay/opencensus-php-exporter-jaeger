<?php

namespace App\Tests\Unit;

use Illuminate\Foundation\Application;
use Laravel\Lumen\Testing\TestCase as LumenTestCase;

class UnitTestCase extends LumenTestCase
{
    protected $baseUrl = 'http://localhost:8000';

    /**
     * Setup the test environment.
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Creates the application.
     * @return Application
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        return $app;
    }

    /**
     * Clean up the testing environment before the next test.
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }
}
