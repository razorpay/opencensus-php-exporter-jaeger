<?php

namespace Unit\Service;

use Mockery;
use Mockery\MockInterface;

class DashBoardTest
{

    const MERCHANT_ID = '10000000000000';
    const USER_ID = '20000000000000';
    const USER_EMAIL = 'test@razorpay.com';
    private $requestMock;
    private $apiService;

    public function setUp(): void
    {
        parent::setUp();
        putenv('APP_API_URL=www.example.com');
        putenv('APP_DASHBOARD_SECRET=some_secret');
        putenv('APP_API_LIVE_USERNAME=live');
        putenv('APP_API_TEST_USERNAME=test');
        $this->setRequestMock(Mockery::mock('overload:App\Request\Requests'));
        $this->setApiService(Mockery::mock('overload:App\Services\Api'));

    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return MockInterface
     */
    public function getRequestMock()
    {
        return $this->requestMock;
    }

    /**
     * @param mixed $requestMock
     */
    public function setRequestMock($requestMock)
    {
        $this->requestMock = $requestMock;
    }

    /**
     * @return MockInterface
     */
    public function getApiService()
    {
        return $this->apiService;
    }

    /**
     * @param mixed $apiService
     */
    public function setApiService($apiService)
    {
        $this->apiService = $apiService;
    }

    /**
     * TODO Resolve this by mocking razorX dependency
     * testGetTokenDataWithNoData should throw exception when no data is available while fetching token.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    //public function testGetTokenDataWithNoData()
    //{
    //    $token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ijl4dTF';
    //    $merchant_id = 'ssskfodkeowkef';
    //    $expectedResponse = new Requests_Response();
    //    $expectedResponse->status_code = 200;
    //
    //    $this->getRequestMock()
    //        ->shouldReceive('get')
    //        ->once()
    //        ->andReturn($expectedResponse);
    //
    //    $this->getApiService()
    //        ->shouldReceive('getOrgHostName')
    //        ->withArgs(['merchant_id'])
    //        ->once();
    //
    //    $dashboard = new Dashboard();
    //    try {
    //        $dashboard->getTokenData(
    //            $token,
    //            $merchant_id
    //        );
    //    } catch (\Exception $ex) {
    //        $this->assertEquals(ErrorCode::BAD_REQUEST_INVALID_CLIENT_OR_USER, $ex->getCode());
    //    }
    //}

    /**
     * TODO Resolve this by mocking razorX dependency
     *testGetTokenDataWithNoData should throw return data if available while fetching token.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    //public function testGetTokenDataWithData()
    //{
    //    $data = [
    //        'user_id' => self::USER_ID,
    //        'user_email' => self::USER_EMAIL,
    //        'merchant_id' => self::MERCHANT_ID,
    //        'role' => 'owner',
    //        'user' => [
    //            'id' => self::USER_ID,
    //            'name' => 'fdfd',
    //            'email' => 'fdsfsd@dfsd.dsfd',
    //            'contact_mobile' => '9999999999',
    //            'created_at' => '1497678977',
    //            'updated_at' => '1497678977',
    //            'merchant_id' => self::MERCHANT_ID,
    //            'confirmed' => true
    //        ],
    //        'query_params' => 'client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=code&amp;scope=read_only'
    //    ];
    //    $mockedData = ['data' => $data];
    //    $expectedResponse = new Requests_Response();
    //    $expectedResponse->status_code = 200;
    //    $expectedResponse->body = json_encode($mockedData);
    //
    //    $this->getRequestMock()
    //        ->shouldReceive('get')
    //        ->once()
    //        ->andReturn($expectedResponse);
    //
    //    $this->getApiService()
    //        ->shouldReceive('getOrgHostName')
    //        ->withArgs(['merchant_id'])
    //        ->once();
    //
    //    $dashboard = new Dashboard();
    //    $response = $dashboard->getTokenData(
    //        'token',
    //        'merchant_id'
    //    );
    //    $this->assertEquals($data, $response);
    //}

}
