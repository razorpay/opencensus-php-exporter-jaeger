<?php

namespace App\Tests\Unit\Service;

use App\Constants\TraceCode;
use App\Error\ErrorCode;
use App\Exception\LogicException;
use App\Services\Api;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Mockery;
use Razorpay\Trace\Facades\Trace;
use Requests_Response;


class ApiTest extends UnitTestCase
{
    private $requestMock;

    /**
     * @return Mockery\MockInterface
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

    public function setUp(): void
    {
        parent::setUp();
        putenv('APP_API_URL=www.example.com');
        putenv('APP_API_LIVE_USERNAME=live');
        putenv('APP_API_TEST_USERNAME=test');
        $this->setRequestMock(Mockery::mock('overload:App\Request\Requests'));

    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * testNotifyMerchant send notification to merchant.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testNotifyMerchant()
    {

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once();
        $api = new Api();
        $api->notifyMerchant(
            'client_id',
            'client_id',
            'client_id'
        );
    }

    /**
     * @Test
     * testNotifyMerchantWithError should handle on notify merchant and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testNotifyMerchantWithError()
    {

        $exception = new Exception('some_error');
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::MERCHANT_NOTIFY_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();
        $api = new Api();
        $api->notifyMerchant(
            'client_id',
            'client_id',
            'client_id'
        );
    }

    /**
     * @Test
     * testSendOTPViaEmail should hit api to send OPT via mail.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testSendOTPViaEmail()
    {
        $expectedResponse = new Requests_Response();
        $expectedResponse->status_code = 200;
        $apiResponse = [
            'success' => true,
        ];
        $expectedResponse->body = json_encode($apiResponse);

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $api = new Api();
        $response = $api->sendOTPViaEmail(
            'client_id',
            'user_id',
            'merchant_id',
            '204391',
            'test@razorpay.com',
            'test'
        );

        $this->assertEquals($expectedResponse->body, json_encode($response));
    }

    /**
     * @Test
     * testSendOTPViaEmail should hit api to send OPT via mail, handle error and trace it
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testSendOTPViaEmailWithNon200HttpCode()
    {
        $expectedResponse = new Requests_Response();
        $expectedResponse->status_code = 400;
        $apiResponse = [
            'message' => ErrorCode::BAD_REQUEST_INVALID_OTP,
        ];
        $expectedResponse->body = json_encode($apiResponse);

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $api = new Api();
        try {
            $api->sendOTPViaEmail(
                'client_id',
                'user_id',
                'merchant_id',
                '204391',
                'test@razorpay.com',
                'test'
            );
        } catch (LogicException $ex) {
            $this->assertEquals('Error when sending OTP via mail.', $ex->getMessage());
        }
    }

    /**
     * @Test
     *testSendOTPViaEmailWithUnknownError  should hit api to send OPT via mail, handle non http error and trace it
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testSendOTPViaEmailWithUnknownError()
    {
        $exception = new Exception('some_error');
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::MERCHANT_NOTIFY_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();
        $api = new Api();
        try {
            $api->sendOTPViaEmail(
                'client_id',
                'user_id',
                'merchant_id',
                '204391',
                'test@razorpay.com',
                'test'
            );
        } catch (LogicException $ex) {
            $this->assertEquals('Error when sending OTP via mail.', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testGetMerchantOrgDetails get merchant Organization details.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGetMerchantOrgDetails()
    {
        $expectedOrg = [
            'email' => 'test@razorpay.com',
            'id' => '100000razorpay',
            'business_name' => 'Razorpay',
            'auth_type' => 'google_auth',
            'default_pricing_plan_id' => 'FL6zMNWhnSUooe'
        ];
        $expectedResponse = new Requests_Response();
        $expectedResponse->body = json_encode($expectedOrg);

        $this->getRequestMock()
            ->shouldReceive('get')
            ->once()
            ->andReturn($expectedResponse);

        $api = new Api();
        $response = $api->getMerchantOrgDetails(
            'abdgejfoisjdrt'
        );

        $this->assertEquals($expectedResponse->body, json_encode($response));
    }

    /**
     * @Test
     * testGetMerchantOrgDetails get merchant Organization details, handle error and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetMerchantOrgDetailsWithError()
    {
        $exception = new Exception('some_error');
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::ORG_DETAILS_FETCH_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();
        $this->getRequestMock()
            ->shouldReceive('get')
            ->once()
            ->andThrow($exception);

        $api = new Api();
        try {
            $api->getMerchantOrgDetails(
                'merchant_id'
            );
        } catch (LogicException $ex) {
            $this->assertEquals('Error when fetching org data', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testGetOrgHostName get Organization Host name.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testGetOrgHostName()
    {
        $mockedBody = ['primary_host_name' => 'some_host_name'];
        $expectedResponse = new Requests_Response();
        $expectedResponse->body = json_encode($mockedBody);

        $this->getRequestMock()
            ->shouldReceive('get')
            ->once()
            ->andReturn($expectedResponse);

        $api = new Api();
        $response = $api->getOrgHostName(
            'merchant_id'
        );

        $this->assertEquals('https://some_host_name', $response);
    }

    /**
     * @Test
     * testGetOrgHostNameWithNoMerchantDetails should return when no merchant details available for host.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetOrgHostNameWithNoMerchantDetails()
    {
        $mockedBody = ['primary_host_name' => 'some_host_name'];
        $expectedResponse = new Requests_Response();
        $expectedResponse->body = json_encode($mockedBody);

        $this->getRequestMock()
            ->shouldReceive('get')
            ->once()
            ->andReturn($expectedResponse);

        $api = new Api();
        try {
            $api->getOrgHostName(
                'merchant_id'
            );
        } catch (LogicException $ex) {
            $this->assertEquals('primary_host_name missing merchant org details', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testMapMerchantToApplication should map merchant to application.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testMapMerchantToApplication()
    {
        $expectedResponse = new Requests_Response();

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        $api = new Api();
        $api->mapMerchantToApplication(
            'app_id',
            'merchant_id',
            'partner_id'
        );
    }

    /**
     * @Test
     * testMapMerchantToApplicationThrowsException should handle error on failing and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testMapMerchantToApplicationThrowsException()
    {
        $exception = new Exception('some_error');
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::MERCHANT_APP_MAPPING_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($exception);

        $api = new Api();
        try {
            $api->mapMerchantToApplication(
                'app_id',
                'merchant_id',
                'partner_id'
            );
        } catch (LogicException $ex) {
            $this->assertEquals('primary_host_name missing merchant org details', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testRevokeMerchantApplicationMapping should revoke mapping between merchant and application.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testRevokeMerchantApplicationMapping()
    {
        $expectedResponse = new Requests_Response();

        $this->getRequestMock()
            ->shouldReceive('delete')
            ->once()
            ->andReturn($expectedResponse);

        $api = new Api();
        $api->revokeMerchantApplicationMapping(
            'app_id',
            'merchant_id'
        );
    }

    /**
     * @Test
     * testRevokeMerchantApplicationMappingThrowsException should handle error and trace it on failing
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testRevokeMerchantApplicationMappingThrowsException()
    {
        $exception = new Exception('some_error');
        $this->getRequestMock()
            ->shouldReceive('delete')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::MERCHANT_APP_MAPPING_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();

        $api = new Api();
        $api->revokeMerchantApplicationMapping(
            'app_id',
            'merchant_id'
        );
    }

    /**
     * @Test
     * testTriggerBankingAccountsWebhook should trigger banking account webhook.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testTriggerBankingAccountsWebhook()
    {
        $expectedResponse = new Requests_Response();

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andReturn($expectedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::BANKING_ACCOUNTS_WEBHOOK_REQUEST, [
                'url' => env('APP_API_URL') . '/merchant/' . 'merchant_id' . '/banking_accounts/',
                'merchantId' => 'merchant_id',
            ]])
            ->once();

        $api = new Api();
        $api->triggerBankingAccountsWebhook(
            'merchant_id',
            'test'
        );
    }

    /**
     * @Test
     * testTriggerBankingAccountsWebhookWithException handle error and trace it.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testTriggerBankingAccountsWebhookWithException()
    {
        $exception = new Exception('some_error');
        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->andThrow($exception);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::BANKING_ACCOUNTS_WEBHOOK_REQUEST, [
                'url' => env('APP_API_URL') . '/merchant/' . 'merchant_id' . '/banking_accounts/',
                'merchantId' => 'merchant_id',
            ]])
            ->shouldReceive('critical')
            ->withArgs([TraceCode::MERCHANT_BANKING_ACCOUNTS_WEBHOOK_FAILED, [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ]])
            ->once();

        $api = new Api();

        try {
            $api->triggerBankingAccountsWebhook(
                'merchant_id',
                'test'
            );
        } catch (LogicException $ex) {
            $this->assertEquals('Error when triggering merchant banking webhooks', $ex->getMessage());
        }
    }

    /**
     * @Test
     * testTriggerBankingAccountsWebhookWithWrongMode throw exception on wrong mode provided.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws LogicException
     */
    public function testTriggerBankingAccountsWebhookWithWrongMode()
    {
        $api = new Api();
        try {
            $api->triggerBankingAccountsWebhook(
                'merchant_id',
                'abc'
            );
        } catch (\Throwable $ex) {
            $this->assertEquals('invalid mode supplied: abc', $ex->getMessage());
        }
    }
}
