<?php

namespace App\Tests;

use App\Tests\Functional\CustomAssertions;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Laravel\Lumen\Testing\TestCase as LumenTestCase;

use Illuminate\Database\Eloquent\Factory;

class TestCase extends LumenTestCase
{
    use DatabaseTransactions;
    use CustomAssertions;

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
     * The auth params set for the test
     *
     * @var array
     */
    protected $auth;

    /**
     * The type of auth set.
     *
     * @var bool
     */
    protected $authType;

    /**
     * @var string|null
     */
    protected $testDataFilePath = null;

    public function setUp(): void
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
        $testData = null;

        if (($this->testDataFilePath !== null) and
            ($testData === null))
        {
            $testData = require $this->testDataFilePath;
        }

        $this->testData = $testData;
    }

    protected function startTest(array $testDataToReplace = [])
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $name = $trace[1]['function'];

        $testData = [];
        if (isset($this->testData[$name]) === true)
        {
            $testData = $this->testData[$name];
        }

        $this->replaceValuesRecursively($testData, $testDataToReplace);

        return $this->runRequestResponseFlow($testData);
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    protected function setInternalAuth(string $username, string $pwd)
    {
        $this->auth = [
            'PHP_AUTH_USER' => $username,
            'PHP_AUTH_PW'   => $pwd
        ];

        $this->authType = 'internal';
    }

    public function getCreds(): array
    {
        return $this->auth ?? [];
    }
}
