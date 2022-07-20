<?php

namespace App\Tests\Unit\Http\Controllers;

use App\Constants\Metric;
use App\Constants\TraceCode;
use App\Http\Controllers\ClientController;
use App\Tests\Unit\UnitTestCase as UnitTestCase;
use Exception;
use Illuminate\Support\Facades\Request as RequestFacade;
use Mockery;
use Mockery\MockInterface;
use Razorpay\Trace\Facades\Trace;

class ClientControllerTest extends UnitTestCase
{
    const MERCHANT_ID = '10000000000000';
    const CLIENT_ID = 'ajJkUghYbJyJJy';
    const CLIENT_SECRET = 'sahfjdkfhjkdsafjkjsadfkjdsafkadsjkf';
    const DEV_ENV = 'dev';
    const PROD_ENV = 'prod';

    private $clientServiceMock;

    public function setUp(): Void
    {
        parent::setUp();
        $this->setClientServiceMock(Mockery::mock('overload:Razorpay\OAuth\Client\Service'));
    }

    public function tearDown(): Void
    {
        parent::tearDown();
    }

    /**
     * @param mixed $clientServiceMock
     */
    public function setClientServiceMock($clientServiceMock)
    {
        $this->clientServiceMock = $clientServiceMock;
    }

    /**
     * @return MockInterface
     */
    public function getClientServiceMock()
    {
        return $this->clientServiceMock;
    }

    /**
     * @Test '/clients'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * createClients should return Entity Array.
     * @return void
     */
    public function testCreateClient()
    {
        $partialResponse = [
            [
                'merchant_id' => self::MERCHANT_ID,
                'environment' => self::DEV_ENV,
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'type' => 'public',
            ], [
                'merchant_id' => self::MERCHANT_ID,
                'environment' => self::PROD_ENV,
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'type' => 'public',
            ]
        ];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_CLIENTS_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getClientServiceMock()
            ->shouldReceive('create')
            ->andReturn($partialResponse);

        $controller = new ClientController();
        $response = $controller->createClients()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/clients'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * refreshClients should return Entity Array.
     * @return void
     * @throws \Throwable
     */
    public function testRefreshClient()
    {
        $partialResponse = [
            [
                'merchant_id' => self::MERCHANT_ID,
                'environment' => self::DEV_ENV,
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'type' => 'public',
            ], [
                'merchant_id' => self::MERCHANT_ID,
                'environment' => self::PROD_ENV,
                'client_id' => self::CLIENT_ID,
                'client_secret' => self::CLIENT_SECRET,
                'type' => 'public',
            ]
        ];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REFRESH_CLIENTS_REQUEST, Mockery::any()])
            ->once()
            ->shouldReceive('count')
            ->withArgs([Metric::REFRESH_CLIENTS_SUCCESS_COUNT])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getClientServiceMock()
            ->shouldReceive('refreshClients')
            ->andReturn($partialResponse);

        $controller = new ClientController();
        $response = $controller->refreshClients()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/clients'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * refreshClients should return Entity Array.
     * @return void
     */
    public function testRefreshClientException()
    {
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REFRESH_CLIENTS_REQUEST, Mockery::any()])
            ->once()
            ->shouldReceive('critical')
            ->withArgs([TraceCode::REFRESH_CLIENTS_REQUEST_FAILURE, Mockery::any()])
            ->once()
            ->shouldReceive('count')
            ->withArgs([Metric::REFRESH_CLIENTS_FAILURE_COUNT])
            ->once();

        $this->getClientServiceMock()
            ->shouldReceive('refreshClients')
            ->andThrow(new Exception('Unknown Issue'));

        try {
            $controller = new ClientController();
            $controller->refreshClients();
        } catch (\Exception $e) {
            $this->assertEquals('Unknown Issue', $e->getMessage());
        }
    }

    /**
     * @Test '/clients/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * delete should return Entity by Entity ID.
     * @return void
     */
    public function testDeleteApplications()
    {
        $id = self::CLIENT_ID;
        $partialResponse = [];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::DELETE_CLIENT_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getClientServiceMock()
            ->shouldReceive('delete')
            ->withArgs([$id, Mockery::any()])
            ->andReturn($partialResponse);

        $controller = new ClientController();
        $response = $controller->delete($id)->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }
}
