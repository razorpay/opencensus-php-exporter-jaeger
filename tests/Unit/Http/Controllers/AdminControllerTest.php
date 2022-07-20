<?php

namespace App\Tests\Unit\Http\Controllers;

use App\Http\Controllers\AdminController;
use App\Tests\Unit\UnitTestCase as UnitTestCase;
use Illuminate\Support\Facades\Request as RequestFacade;
use Mockery;
use Mockery\MockInterface;
use Razorpay\OAuth\Base\Table;

class AdminControllerTest extends UnitTestCase
{
    const MERCHANT_ID = '10000000000000';
    const CLIENT_ID = 'ajJkUghYbJyJJy';
    const CLIENT_SECRET = 'sahfjdkfhjkdsafjkjsadfkjdsafkadsjkf';
    const DEV_ENV = 'dev';
    const PROD_ENV = 'prod';

    private $adminServiceMock;

    public function setUp(): Void
    {
        parent::setUp();
        $this->setAdminServiceMock(Mockery::mock('overload:App\Models\Admin\Service'));
    }

    public function tearDown(): Void
    {
        parent::tearDown();
    }

    /**
     * @param mixed $adminServiceMock
     */
    public function setAdminServiceMock($adminServiceMock)
    {
        $this->adminServiceMock = $adminServiceMock;
    }

    /**
     * @return MockInterface
     */
    public function getAdminServiceMock()
    {
        return $this->adminServiceMock;
    }

    /**
     * @Test '/'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchMultipleForAdmin should return Entity Array.
     * @return void
     */
    public function testFetchMultipleForAdmin()
    {
        $entityType = Table::CLIENTS;
        RequestFacade::shouldReceive('all')->andReturn([]);
        $mockedResponse = [
            'entity' => 'collection',
            'count' => 2,
            'admin' => true,
            'items' => [
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
            ]
        ];
        $this->getAdminServiceMock()
            ->shouldReceive('fetchMultipleForAdmin')
            ->withArgs([$entityType, []])
            ->andReturn($mockedResponse);

        $controller = new AdminController();
        $response = $controller->fetchMultipleForAdmin($entityType)->getContent();

        $this->assertEquals(json_encode($mockedResponse), $response);
    }

    /**
     * @Test '/'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * fetchByIdForAdmin should return Entity by Entity ID.
     * @return void
     */
    public function testFetchByIdForAdmin()
    {
        $entityType = Table::CLIENTS;
        $entityId = self::MERCHANT_ID;
        $mockedResponse = [
            'merchant_id' => self::MERCHANT_ID,
            'environment' => self::DEV_ENV,
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'type' => 'public',
        ];
        $this->getAdminServiceMock()
            ->shouldReceive('fetchByIdForAdmin')
            ->withArgs([$entityType, $entityId])
            ->andReturn($mockedResponse);

        $controller = new AdminController();
        $response = $controller->fetchByIdForAdmin($entityType, $entityId)->getContent();

        $this->assertEquals(json_encode($mockedResponse), $response);
    }
}
