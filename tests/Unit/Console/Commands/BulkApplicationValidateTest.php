<?php

namespace App\Tests\Unit\Console\Commands;


use App\Console\Commands\BulkApplicationValidate;
use App\Constants\TraceCode;
use App\Exception\NotFoundException;
use App\Tests\Unit\UnitTestCase;
use Mockery;
use Razorpay\OAuth\Application\Entity;
use Razorpay\OAuth\Client\Entity as Client;
use Razorpay\OAuth\Client\Type;
use Razorpay\Trace\Facades\Trace;

class BulkApplicationValidateTest extends UnitTestCase
{
    private $applicationServiceMock;
    private $edgeServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setApplicationServiceMock(Mockery::mock('overload:Razorpay\OAuth\Application\Service'));
        $this->setEdgeServiceMock(Mockery::mock('overload:App\Services\EdgeService'));
        putenv("BULK_APPLICATION_CREATE_APP_NAME=MyApp");
        putenv("BULK_APPLICATION_CREATE_APP_TYPE=mobile_app");
        putenv("BULK_APPLICATION_CREATE_APP_WEBSITE=https://www.example.com");
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function setApplicationServiceMock($applicationServiceMock)
    {
        $this->applicationServiceMock = $applicationServiceMock;
    }

    public function setEdgeServiceMock($edgeServiceMock)
    {
        $this->edgeServiceMock = $edgeServiceMock;
    }

    public function getApplicationServiceMock()
    {
        return $this->applicationServiceMock;
    }

    public function getEdgeServiceMock()
    {
        return $this->edgeServiceMock;
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
        $command = new BulkApplicationValidate();
        $this->assertEquals('Validate Oauth Client Sync with edge', $command->getDescription());
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

        Trace::shouldReceive('error')
            ->withArgs([TraceCode::VALIDATE_CLIENT_EDGE_SYNC, Mockery::any()])
            ->once();

        $command = new BulkApplicationValidate();
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
    public function testIfClientsAreSyncedToEdge(): void
    {
        putenv('BULK_APPLICATION_CREATE_FILE_NAME=sample.csv');

        $application = $this->getApplication();
        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andReturn([
                'count' => 1,
                'items' => [$application]
            ]);

        $this->getEdgeServiceMock()
            ->shouldReceive('getOauth2Client')
            ->andReturn()
            ->once();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::VALIDATE_CLIENT_EDGE_SYNC, Mockery::any()])
            ->twice();

        $command = new BulkApplicationValidate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testIfClientsAreNotSyncedToEdge validates when clients are not synced to edge
     * when an application already exists for the MID
     * @return void
     */
    public function testIfClientsAreNotSyncedToEdge(): void
    {
        putenv('BULK_APPLICATION_CREATE_FILE_NAME=sample.csv');

        $application = $this->getApplication();
        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andReturn([
                'count' => 1,
                'items' => [$application]
            ]);

        $this->getEdgeServiceMock()
            ->shouldReceive('getOauth2Client')
            ->andThrow(new NotFoundException("oauth2 client is not present."));


        Trace::shouldReceive('error')
            ->withArgs([TraceCode::VALIDATE_CLIENT_EDGE_SYNC, Mockery::any()])
            ->once();

        $command = new BulkApplicationValidate();
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


        Trace::shouldReceive('error')
            ->withArgs([TraceCode::VALIDATE_CLIENT_EDGE_SYNC, Mockery::any()])
            ->once();

        $command = new BulkApplicationValidate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandlerWithZeroApplicationsReturned validates that Edge call is not made when no applications are returned
     * @return void
     */
    public function testHandlerWithZeroApplicationsReturned(): void
    {
        putenv('BULK_APPLICATION_CREATE_FILE_NAME=sample.csv');


        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andReturn([
                'count' => 0,
                'items' => []
            ]);

        $this->getEdgeServiceMock()
            ->shouldReceive('getOauth2Client')
            ->andReturn()
            ->never();


        Trace::shouldReceive('info')
            ->withArgs([TraceCode::VALIDATE_CLIENT_EDGE_SYNC, [
                'merchant_id' => '10000000000001',
                'message' => 'zero client at auth service']])
            ->once();

        $command = new BulkApplicationValidate();
        $command->handle();
    }

    private function getApplication(): Entity
    {
        $application = new Entity();
        $application->setAttribute("merchant_id", "10000000000001");
        $input[Client::MERCHANT_ID] = $application->getMerchantId();
        $input[Client::ENVIRONMENT] = 'dev';
        $input[Client::TYPE] = Type::PUBLIC;
        $client = (new Client)->build($input);
        $client['id'] = '1234';
        $client->application()->associate($application);
        $application["clients"] = [$client];
        return $application;
    }

}



