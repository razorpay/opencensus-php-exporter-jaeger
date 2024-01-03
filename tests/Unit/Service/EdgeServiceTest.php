<?php

namespace Unit\Service;

use App\Constants\Metric;
use App\Constants\TraceCode;
use App\Exception\LogicException;
use App\Models\Auth\Constant;
use App\Services\EdgeService;
use App\Tests\Unit\UnitTestCase;
use Mockery;
use Mockery\MockInterface;
use Razorpay\OAuth\Token\Mode;
use Razorpay\Trace\Facades\Trace;
use Razorpay\Trace\Logger;
use WpOrg\Requests\Response as RequestsResponse;

class EdgeServiceTest extends UnitTestCase
{
    private $requestMock;
    private $createIdentifierPayload;

    public function setUp(): void
    {
        parent::setUp();
        $this->setRequestMock(Mockery::mock('overload:App\Request\Requests'));
        $this->createIdentifierPayload = [
            'kid' => 'kid',
            'jti' => 'jti',
            'user_id' => 'user_id',
            'tags' => ['r~read_only', 'm~t'],
            'ttl' => 'ttl',
            'ref_id' => 'client_id'
        ];
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
     * @Test
     * testPostPublicIdToEdgeWithNoConsumer should sync Identifier and create Consumer if not exists but publishing identifier.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testPostPublicIdToEdgeWithNoConsumer()
    {
        $createIdentifier = new RequestsResponse();
        $createIdentifier->status_code = 404;

        $createConsumer2 = new RequestsResponse();
        $createConsumer2->status_code = 200;
        $createConsumer2->success = true;

        $createIdentifier1 = new RequestsResponse();
        $createIdentifier1->status_code = 200;
        $createIdentifier1->success = true;

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($createIdentifier)
            ->shouldReceive('post')
            ->once()
            ->andReturn($createIdentifier1)
            ->shouldReceive('post')
            ->once()
            ->andReturn($createIdentifier1);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE,  [
                'merchant_id'   => 'merchant_id',
                'request_body'  => $this->createIdentifierPayload,
            ]])
            ->twice();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_CONSUMER_IN_EDGE, Mockery::any()])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => true,
                Metric::LABEL_ATTEMPTS => 1,
            ]])
            ->once();

        $edgeService = new EdgeService([],'www.example.com', 'some_secret');
        $edgeService->postPublicIdToEdge(
            [
                Constant::MID => 'merchant_id',
                Constant::PUBLIC_TOKEN => 'kid',
                Constant::IDENTIFIER => 'jti',
                Constant::USER_ID => 'user_id',
                Constant::MODE => Mode::TEST,
                Constant::TTL => 'ttl',
                Constant::CLIENT_ID => 'client_id',
                Constant::SCOPES => ['read_only']
            ]);
    }

    /**
     * @Test
     * testPostPublicIdToEdgeWithConsumer should create Identifier on Edge Side.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testPostPublicIdToEdgeWithConsumer()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 200;
        $expectedResponse->success = true;

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE, [
                'merchant_id'   => 'merchant_id',
                'request_body'  => $this->createIdentifierPayload,
            ]])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => true,
                Metric::LABEL_ATTEMPTS => 1,
            ]])
            ->once();

        $edgeService = new EdgeService([], 'www.example.com', 'some_secret');
        $edgeService->postPublicIdToEdge(
            [
                Constant::MID => 'merchant_id',
                Constant::PUBLIC_TOKEN => 'kid',
                Constant::IDENTIFIER => 'jti',
                Constant::USER_ID => 'user_id',
                Constant::MODE => Mode::TEST,
                Constant::TTL => 'ttl',
                Constant::CLIENT_ID => 'client_id',
                Constant::SCOPES => ['read_only']
            ]);
    }

    /**
     * @Test
     * testPostPublicIdToEdgeWithCreateIdentifierFailing should throw Exception and Trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testPostPublicIdToEdgeWithCreateIdentifierFailing()
    {
        $createIdentifier = new RequestsResponse();
        $createIdentifier->status_code = 400;
        $createIdentifier->success = false;
        $maxAttempts = 3;

        $this->getRequestMock()
            ->shouldReceive('post')
            ->andReturn($createIdentifier)
            ->times($maxAttempts);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE, [
                'merchant_id'   => 'merchant_id',
                'request_body'  => $this->createIdentifierPayload,
            ]])
            ->times($maxAttempts);

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
                Metric::LABEL_ATTEMPTS => 1,
            ]])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
                Metric::LABEL_ATTEMPTS => 2,
            ]])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
                Metric::LABEL_ATTEMPTS => 3,
            ]])
            ->once();

        Trace::shouldReceive('traceException')
            ->withArgs([Mockery::any(), Logger::ERROR, TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_CASSANDRA_FAILED])
            ->times($maxAttempts);

        $edgeService = new EdgeService([], 'www.example.com', 'some_secret');
        try {
            $edgeService->postPublicIdToEdge(
                [
                    Constant::MID => 'merchant_id',
                    Constant::PUBLIC_TOKEN => 'kid',
                    Constant::IDENTIFIER => 'jti',
                    Constant::USER_ID => 'user_id',
                    Constant::MODE => Mode::TEST,
                    Constant::TTL => 'ttl',
                    Constant::CLIENT_ID => 'client_id',
                    Constant::SCOPES => ['read_only']
                ]);
        } catch (\Exception $ex) {
            $this->assertEquals('Could not create identifier in edge', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testPostPublicIdToEdgeWithCreateConsumerFailing should throw Exception on failing create Consumer and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testPostPublicIdToEdgeWithCreateConsumerFailing()
    {
        $createIdentifier = new RequestsResponse();
        $createIdentifier->status_code = 404;

        $createConsumer1 = new RequestsResponse();
        $createConsumer1->status_code = 400;
        $createConsumer1->success = false;
        $maxAttempts = 3;

        $this->getRequestMock()
            ->shouldReceive('post')
            ->andReturn($createIdentifier)
            ->shouldReceive('post')
            ->andReturn($createConsumer1);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE, [
                'merchant_id'   => 'merchant_id',
                'request_body'  => $this->createIdentifierPayload,
            ]])
            ->times($maxAttempts);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_CONSUMER_IN_EDGE, Mockery::any()])
            ->times($maxAttempts);

        Trace::shouldReceive('traceException')
            ->withArgs([Mockery::any(), Logger::ERROR, TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE_CASSANDRA_FAILED])
            ->times($maxAttempts);

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
                Metric::LABEL_ATTEMPTS => 1,
            ]])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
                Metric::LABEL_ATTEMPTS => 2,
            ]])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
                Metric::LABEL_ATTEMPTS => 3,
            ]])
            ->once();

        $edgeService = new EdgeService([], 'www.example.com', 'some_secret');
        try {
            $edgeService->postPublicIdToEdge(
                [
                    Constant::MID => 'merchant_id',
                    Constant::PUBLIC_TOKEN => 'kid',
                    Constant::IDENTIFIER => 'jti',
                    Constant::USER_ID => 'user_id',
                    Constant::MODE => Mode::TEST,
                    Constant::TTL => 'ttl',
                    Constant::CLIENT_ID => 'client_id',
                    Constant::SCOPES => ['read_only']
                ]);
        } catch (\Exception $ex) {
            $this->assertEquals('Could not create consumer in edge', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testGetOauth2ClientSuccess checks if oauth2 client is present on edge
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGetOauth2ClientSuccess()
    {
        $clientId="123";
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 200;
        $expectedResponse->success = true;

        $this->getRequestMock()
            ->shouldReceive('get')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_OAUTH_CLIENT_FROM_EDGE, Mockery::any()])
            ->once();


        $edgeService = new EdgeService([], 'www.example.com', 'some_secret');
        $edgeService->getOauth2Client($clientId);
    }

    /**
     * @Test
     * testGetOauth2ClientNotFound throws exception if oauth2 client is absent on edge
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGetOauth2ClientNotFound()
    {
        $clientId="123";
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 404;
        $expectedResponse->success = false;

        $this->getRequestMock()
            ->shouldReceive('get')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_OAUTH_CLIENT_FROM_EDGE, Mockery::any()])
            ->once();

        $edgeService = new EdgeService([], 'www.example.com', 'some_secret');
        try {
            $edgeService->getOauth2Client($clientId);
        } catch (\Exception $ex) {
            $this->assertEquals("oauth2 client is not present.", $ex->getMessage());
        }
    }

    /**
     * @Test
     * testGetOauth2ClientRequestError throws exception if oauth2 client request to Edge fails
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGetOauth2ClientRequestError()
    {
        $clientId="123";
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 500;
        $expectedResponse->success = false;

        $this->getRequestMock()
            ->shouldReceive('get')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_OAUTH_CLIENT_FROM_EDGE, Mockery::any()])
            ->once();

        $edgeService = new EdgeService([], 'www.example.com', 'some_secret');
        try {
            $edgeService->getOauth2Client($clientId);
        } catch (\Exception $ex) {
            $this->assertEquals("Request to find oauth2 client in edge failed", $ex->getMessage());
        }
    }
}
