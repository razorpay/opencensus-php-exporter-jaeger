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
use Requests_Response;

class EdgeServiceTest extends UnitTestCase
{
    private $requestMock;

    public function setUp(): void
    {
        parent::setUp();
        putenv('EDGE_URL=www.example.com');
        putenv('EDGE_SECRET=some_secret');
        $this->setRequestMock(Mockery::mock('overload:App\Request\Requests'));
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
        $createIdentifier = new Requests_Response();
        $createIdentifier->status_code = 404;

        $createConsumer2 = new Requests_Response();
        $createConsumer2->status_code = 200;
        $createConsumer2->success = true;

        $createIdentifier1 = new Requests_Response();
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
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE, Mockery::any()])
            ->twice();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_CONSUMER_IN_EDGE, Mockery::any()])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => true,
            ]])
            ->once();

        $edgeService = new EdgeService([]);
        $edgeService->postPublicIdToEdge(
            [
                Constant::MID => 'merchant_id',
                Constant::PUBLIC_TOKEN => 'kid',
                Constant::IDENTIFIER => 'jti',
                Constant::USER_ID => 'user_id',
                Constant::MODE => Mode::TEST,
                Constant::TTL => 'ttl',
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
        $expectedResponse = new Requests_Response();
        $expectedResponse->status_code = 200;
        $expectedResponse->success = true;

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE, Mockery::any()])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => true,
            ]])
            ->once();

        $edgeService = new EdgeService([]);
        $edgeService->postPublicIdToEdge(
            [
                Constant::MID => 'merchant_id',
                Constant::PUBLIC_TOKEN => 'kid',
                Constant::IDENTIFIER => 'jti',
                Constant::USER_ID => 'user_id',
                Constant::MODE => Mode::TEST,
                Constant::TTL => 'ttl',
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
        $createIdentifier = new Requests_Response();
        $createIdentifier->status_code = 400;
        $createIdentifier->success = false;

        $this->getRequestMock()
            ->shouldReceive('post')
            ->andReturn($createIdentifier);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE, Mockery::any()])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
            ]])
            ->once();

        $edgeService = new EdgeService([]);
        try {
            $edgeService->postPublicIdToEdge(
                [
                    Constant::MID => 'merchant_id',
                    Constant::PUBLIC_TOKEN => 'kid',
                    Constant::IDENTIFIER => 'jti',
                    Constant::USER_ID => 'user_id',
                    Constant::MODE => Mode::TEST,
                    Constant::TTL => 'ttl',
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
        $createIdentifier = new Requests_Response();
        $createIdentifier->status_code = 404;

        $createConsumer1 = new Requests_Response();
        $createConsumer1->status_code = 400;
        $createConsumer1->success = false;

        $this->getRequestMock()
            ->shouldReceive('post')
            ->andReturn($createIdentifier)
            ->shouldReceive('post')
            ->andReturn($createConsumer1);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_OAUTH_IDENTIFIER_IN_EDGE, Mockery::any()])
            ->once();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_CONSUMER_IN_EDGE, Mockery::any()])
            ->once();

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::HTTP_REQUEST_EDGE_IDENTIFIER, Mockery::any(), [
                Metric::LABEL_STATUS => false,
            ]])
            ->once();

        $edgeService = new EdgeService([]);
        try {
            $edgeService->postPublicIdToEdge(
                [
                    Constant::MID => 'merchant_id',
                    Constant::PUBLIC_TOKEN => 'kid',
                    Constant::IDENTIFIER => 'jti',
                    Constant::USER_ID => 'user_id',
                    Constant::MODE => Mode::TEST,
                    Constant::TTL => 'ttl',
                ]);
        } catch (\Exception $ex) {
            $this->assertEquals('Could not create consumer in edge', $ex->getMessage());
        }
    }
}
