<?php

namespace App\Tests\Unit\Console\Commands;


use App\Console\Commands\BulkApplicationCreate;
use App\Constants\TraceCode;
use App\Tests\Unit\UnitTestCase;
use Mockery;
use Razorpay\OAuth\Application\Entity;
use Razorpay\Trace\Facades\Trace;

class BulkApplicationCreateTest extends UnitTestCase
{
    private $applicationServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setApplicationServiceMock(Mockery::mock('overload:Razorpay\OAuth\Application\Service'));
        putenv("BULK_APPLICATION_CREATE_APP_NAME=MyApp");
        putenv("BULK_APPLICATION_CREATE_APP_TYPE=mobile_app");
        putenv("BULK_APPLICATION_CREATE_APP_WEBSITE=https://www.example.com");
        putenv("BULK_APPLICATION_CREATE_USE_SEPARATE_OUTBOX_JOB=true");
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function setApplicationServiceMock($applicationServiceMock)
    {
        $this->applicationServiceMock = $applicationServiceMock;
    }

    public function getApplicationServiceMock()
    {
        return $this->applicationServiceMock;
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testDescription validates if description set matches with the description that we get from getter
     * @return void
     */
    public function testDescription(): void
    {
        $command = new BulkApplicationCreate();
        $this->assertEquals('Create OAuth Applications for a list of Merchant IDs', $command->getDescription());
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandlerWithInvalidFileName validates that an error is logged when an invalid file name is provided
     * @return void
     */
    public function testHandlerWithInvalidFileName(): void
    {
        putenv("BULK_APPLICATION_CREATE_FILE_NAME=non_existent_file.csv");

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::ERROR_EXCEPTION, Mockery::any()])
            ->once();

        $command = new BulkApplicationCreate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandlerWithAlreadyExistingApplication validates that a new application is not created
     * when an application already exists for the MID
     * @return void
     */
    public function testHandlerWithAlreadyExistingApplication(): void
    {
        putenv('BULK_APPLICATION_CREATE_FILE_NAME=sample.csv');

        $application = new Entity();
        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andReturn([
                'count' => 1,
                'items' => [$application]
            ]);


        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_APPLICATION_REQUEST, Mockery::any()])
            ->once();

        // Create should not execute at all
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_APPLICATION_REQUEST, Mockery::any()])
            ->never();

        $command = new BulkApplicationCreate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandlerWithNewApplication validates that a new application is created
     * when an application does not exist for the MID
     * @return void
     */
    public function testHandlerWithNewApplication(): void
    {
        putenv('BULK_APPLICATION_CREATE_FILE_NAME=sample.csv');

        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andReturn([
                'count' => 0,
                'items' => []
            ]);

        $this->getApplicationServiceMock()
            ->shouldReceive('create')
            ->andReturn(new Entity());

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_APPLICATION_REQUEST, Mockery::any()])
            ->once();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_APPLICATION_REQUEST, Mockery::any()])
            ->once();

        $command = new BulkApplicationCreate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandlerWithErrorThrownByFetch validates that an error is logged when fetch throws an error
     * @return void
     */
    public function testHandlerWithErrorThrownByFetch(): void
    {
        putenv('BULK_APPLICATION_CREATE_FILE_NAME=sample.csv');


        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andThrow(new \Exception("Some exception"));

        $this->getApplicationServiceMock()
            ->shouldReceive('create')
            ->andReturn(new Entity());

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_APPLICATION_REQUEST, Mockery::any()])
            ->never();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_APPLICATION_REQUEST, Mockery::any()])
            ->never();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::ERROR_EXCEPTION, Mockery::any()])
            ->once();

        $command = new BulkApplicationCreate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandlerWithErrorThrownByCreate validates that an error is logged when create throws an error
     * @return void
     */
    public function testHandlerWithErrorThrownByCreate(): void
    {
        putenv('BULK_APPLICATION_CREATE_FILE_NAME=sample.csv');


        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andReturn([
                'count' => 0,
                'items' => []
            ]);

        $this->getApplicationServiceMock()
            ->shouldReceive('create')
            ->andThrow(new \Exception("Some exception"));

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_APPLICATION_REQUEST, Mockery::any()])
            ->once();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_APPLICATION_REQUEST, Mockery::any()])
            ->never();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::ERROR_EXCEPTION, Mockery::any()])
            ->once();

        $command = new BulkApplicationCreate();
        $command->handle();
    }
}



