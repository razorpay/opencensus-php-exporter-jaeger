<?php

namespace Unit\Models\Admin;

use App\Models\Admin\Service;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Illuminate\Support\Facades\Request;
use Mockery;
use Razorpay\OAuth\Base\Table;

class ServiceTest extends UnitTestCase
{
    private $applicationServiceMock;
    private $clientServiceMock;
    private $refreshTokenServiceMock;
    private $tokenServiceMock;

    const EXCEPTION = "some_error_message";

    public function setUp(): void
    {
        parent::setUp();
        $this->setApplicationServiceMock(Mockery::mock('overload:Razorpay\OAuth\Application\Service'));
        $this->setClientServiceMock(Mockery::mock('overload:Razorpay\OAuth\Client\Service'));
        $this->setRefreshTokenServiceMock(Mockery::mock('overload:Razorpay\OAuth\RefreshToken\Service'));
        $this->setTokenServiceMock(Mockery::mock('overload:Razorpay\OAuth\Token\Service'));

    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return mixed
     */
    public function getApplicationServiceMock()
    {
        return $this->applicationServiceMock;
    }

    /**
     * @param mixed $applicationServiceMock
     */
    public function setApplicationServiceMock($applicationServiceMock)
    {
        $this->applicationServiceMock = $applicationServiceMock;
    }

    /**
     * @return mixed
     */
    public function getClientServiceMock()
    {
        return $this->clientServiceMock;
    }

    /**
     * @param mixed $clientServiceMock
     */
    public function setClientServiceMock($clientServiceMock)
    {
        $this->clientServiceMock = $clientServiceMock;
    }

    /**
     * @return mixed
     */
    public function getRefreshTokenServiceMock()
    {
        return $this->refreshTokenServiceMock;
    }

    /**
     * @param mixed $refreshTokenServiceMock
     */
    public function setRefreshTokenServiceMock($refreshTokenServiceMock)
    {
        $this->refreshTokenServiceMock = $refreshTokenServiceMock;
    }

    /**
     * @return Mockery\MockInterface
     */
    public function getTokenServiceMock()
    {
        return $this->tokenServiceMock;
    }

    /**
     * @param mixed $tokenServiceMock
     */
    public function setTokenServiceMock($tokenServiceMock)
    {
        $this->tokenServiceMock = $tokenServiceMock;
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchMultipleForAdminFromApplicationService should call fetchMultipleAdmin from application service class.
     * @return void
     */
    public function testFetchMultipleForAdminFromApplicationService()
    {
        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultipleAdmin')
            ->andReturn([]);

        $service = new Service();
        $input = Request::all();

        $response = $service->fetchMultipleForAdmin(Table::APPLICATIONS, $input);
        $this->assertEquals([], $response);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchMultipleForAdminFromClientService should call fetchMultipleAdmin from Client service class.
     * @return void
     */
    public function testFetchMultipleForAdminFromClientService()
    {
        $this->getClientServiceMock()
            ->shouldReceive('fetchMultipleAdmin')
            ->andReturn([]);

        $service = new Service();
        $input = Request::all();

        $response = $service->fetchMultipleForAdmin(Table::CLIENTS, $input);
        $this->assertEquals([], $response);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchMultipleForAdminFromRefreshTokenService should call fetchMultipleAdmin from RefreshToken service class.
     * @return void
     */
    public function testFetchMultipleForAdminFromRefreshTokenService()
    {
        $this->getRefreshTokenServiceMock()
            ->shouldReceive('fetchMultipleAdmin')
            ->andReturn([]);

        $service = new Service();
        $input = Request::all();

        $response = $service->fetchMultipleForAdmin(Table::REFRESH_TOKENS, $input);
        $this->assertEquals([], $response);
    }


    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchMultipleForAdminFromTokenService should call fetchMultipleAdmin from Token service class.
     * @return void
     */
    public function testFetchMultipleForAdminFromTokenService()
    {
        $this->getTokenServiceMock()
            ->shouldReceive('fetchMultipleAdmin')
            ->andReturn([]);

        $service = new Service();
        $input = Request::all();

        $response = $service->fetchMultipleForAdmin(Table::TOKENS, $input);
        $this->assertEquals([], $response);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchMultipleForAdminFromTokenService should call fetchMultipleAdmin from Token service and Throws Exception.
     * @return void
     */
    public function testFetchMultipleForAdminWhenTokenServiceThrowsException()
    {
        $this->getTokenServiceMock()
            ->shouldReceive('fetchMultipleAdmin')
            ->andThrow(new Exception(self::EXCEPTION));

        $service = new Service();
        $input = Request::all();

        try {
            $service->fetchMultipleForAdmin(Table::TOKENS, $input);
        } catch (Exception $ex) {
            $this->assertEquals(self::EXCEPTION, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchByIdForAdminFromApplicationService should call fetchAdmin from application service class.
     * @return void
     */
    public function testFetchByIdForAdminFromApplicationService()
    {
        $entityId = 'some_id';
        $this->getApplicationServiceMock()
            ->shouldReceive('fetchAdmin')
            ->with($entityId)
            ->andReturn([]);

        $service = new Service();

        $response = $service->fetchByIdForAdmin(Table::APPLICATIONS, $entityId);
        $this->assertEquals([], $response);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchByIdForAdminFromClientService should call fetchAdmin from Client service class.
     * @return void
     */
    public function testFetchByIdForAdminFromClientService()
    {
        $entityId = 'some_id';
        $this->getClientServiceMock()
            ->shouldReceive('fetchAdmin')
            ->andReturn([]);

        $service = new Service();

        $response = $service->fetchByIdForAdmin(Table::CLIENTS, $entityId);
        $this->assertEquals([], $response);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchByIdForAdminFromRefreshTokenService should call fetchAdmin from RefreshToken service class.
     * @return void
     */
    public function testFetchByIdForAdminFromRefreshTokenService()
    {
        $entityId = 'some_id';
        $this->getRefreshTokenServiceMock()
            ->shouldReceive('fetchAdmin')
            ->andReturn([]);

        $service = new Service();

        $response = $service->fetchByIdForAdmin(Table::REFRESH_TOKENS, $entityId);
        $this->assertEquals([], $response);
    }


    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchByIdForAdminFromTokenService should call fetchAdmin from Token service class.
     * @return void
     */
    public function testFetchByIdForAdminFromTokenService()
    {
        $entityId = 'some_id';
        $this->getTokenServiceMock()
            ->shouldReceive('fetchAdmin')
            ->andReturn([]);

        $service = new Service();

        $response = $service->fetchByIdForAdmin(Table::TOKENS, $entityId);
        $this->assertEquals([], $response);
    }
}
