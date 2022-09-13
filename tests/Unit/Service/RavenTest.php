<?php

namespace Unit\Service;

use App\Constants\TraceCode;
use App\Error\ErrorCode;
use App\Exception\LogicException;
use App\Services\Raven;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Razorpay\Trace\Facades\Trace;
use WpOrg\Requests\Response as RequestsResponse;

class RavenTest extends UnitTestCase
{

    const ERROR_MESSAGE = 'some_error';
    private $requestMock;

    public function setUp(): void
    {
        parent::setUp();
        putenv('APP_RAVEN_URL=www.example.com');
        putenv('APP_RAVEN_SECRET=some_secret');
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
     * testGenerateOtp should generate OTP.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGenerateOtp()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 200;
        $generateOTP = ['otp' => '0007'];
        $expectedResponse->body = json_encode($generateOTP);

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $raven = new Raven();
        $response = $raven->generateOTP(
            'login_id',
            'some_context'
        );

        $this->assertEquals($expectedResponse->body, json_encode($response));
    }

    /**
     * @Test
     * testGenerateOtpWithRequestFailed should handle error and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGenerateOtpWithRequestFailed()
    {
        $exception = new Exception(self::ERROR_MESSAGE);
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::RAVEN_GENERATE_OTP_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();

        $raven = new Raven();
        try {
            $raven->generateOTP(
                'login_id',
                'some_context'
            );
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * testGenerateOtpWithNon200Http should throw api error and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGenerateOtpWithNon200Http()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 400;

        $generateOTP = ['message' => ErrorCode::BAD_REQUEST_INVALID_OTP];
        $expectedResponse->body = json_encode($generateOTP);

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::RAVEN_GENERATE_OTP_FAILED, json_decode($expectedResponse->body, true)])
            ->once();

        $raven = new Raven();
        try {
            $raven->generateOTP(
                'login_id',
                'some_context'
            );
        } catch (Exception $ex) {
            $this->assertEquals(ErrorCode::BAD_REQUEST_OTP_GENERATION_FAILED, $ex->getCode());
        }
    }

    /**
     * @Test
     * testVerifyOTP should verify OTP.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testVerifyOTP()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 200;

        $generateOTP = ['otp' => '0007'];
        $expectedResponse->body = json_encode($generateOTP);

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $raven = new Raven();
        $response = $raven->verifyOTP(
            'login_id',
            'some_context',
            '123456'
        );

        $this->assertEquals($expectedResponse->body, json_encode($response));
    }

    /**
     * @Test
     * testVerifyOTPWithRequestFailed should throw exception and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testVerifyOTPWithRequestFailed()
    {
        $exception = new Exception('some_error');
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::RAVEN_VERIFY_OTP_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();

        $raven = new Raven();
        try {
            $raven->verifyOTP(
                'login_id',
                'some_context',
                '123456'
            );
        } catch (Exception $ex) {
            $this->assertEquals('some_error', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testVerifyOTPWithNon200Http should throw Exception on non 200 response and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testVerifyOTPWithNon200Http()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 400;

        $generateOTP = ['message' => ErrorCode::BAD_REQUEST_INVALID_OTP];
        $expectedResponse->body = json_encode($generateOTP);

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::RAVEN_VERIFY_OTP_FAILED, json_decode($expectedResponse->body, true)])
            ->once();

        $raven = new Raven();
        try {
            $raven->verifyOTP(
                'login_id',
                'some_context',
                '123456'
            );
        } catch (Exception $ex) {
            $this->assertEquals(ErrorCode::BAD_REQUEST_INVALID_OTP, $ex->getCode());
        }
    }
}
