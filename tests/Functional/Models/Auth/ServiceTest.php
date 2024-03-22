<?php

namespace Functional\Models\Auth;

use App\Tests\TestCase;
use Razorpay\OAuth\Scope;
use App\Models\Auth\Service;
use Razorpay\OAuth\Scope\ScopeConstants;
use App\Tests\Functional\AuthController\UtilityTrait;

class ServiceTest extends TestCase
{
    use UtilityTrait;

    private $oauthServerMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->oauthServerMock = \Mockery::mock('Razorpay\OAuth\OAuthServer');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetAuthorizeViewData() : void
    {
        config(['trace.services.splitz.mock' => true]);

        $input = [
            'state'         => '123',
            'scope'         => 'read_only',
            'client_id'     => '30000000000000',
            'redirect_uri'  => 'https://www.example.com',
            'response_type' => 'code'
        ];

        $this->createAndSetClientWithEnvironment();

        $service = new Service();
        $service->oauthServer = $this->oauthServerMock;

        $this->oauthServerMock
            ->shouldReceive('validateAuthorizeRequestAndGetScopes')
            ->andReturn(collect(
                [
                    new Scope\Entity(ScopeConstants::READ_ONLY),
                ]));

        $response = $service->getAuthorizeViewData($input);

        $this->assertEquals("10000000000000", $response["application"]["merchant_id"]);
        $this->assertEquals($this->application->getId(), $response["application"]["id"]);
        $this->assertEquals("https://api-test.com", $response['dashboard_url']);
        $this->assertNotNull($response['custom_policy_url']);
        $this->assertNotNull($response['onboarding_url']);
        $this->assertTrue($response['is_platform_fee_enabled']);
    }

    public function testGetAuthorizeViewDataForPhantom()
    {
        $redirectUrl = 'https://test.com';

        config([
            'trace.services.splitz.mock'        => true,
            'trace.app.phantom_onboarding_url'  => $redirectUrl
       ]);

        $input = [
            'state'         => '123',
            'scope'         => 'read_only',
            'client_id'     => '30000000000000',
            'redirect_uri'  => 'https://www.example.com',
            'response_type' => 'code'
        ];

        $this->createAndSetClientWithEnvironment();

        $expectedRedirectUrl = $redirectUrl . '?applicationId=' . $this->application->getId();

        $service = new Service();
        $service->oauthServer = $this->oauthServerMock;

        $this->oauthServerMock
            ->shouldReceive('validateAuthorizeRequestAndGetScopes')
            ->andReturn(collect(
                [
                    new Scope\Entity(ScopeConstants::READ_ONLY),
                ]));

        $response = $service->getAuthorizeViewData($input);

        $this->assertEquals("10000000000000", $response["application"]["merchant_id"]);
        $this->assertEquals($this->application->getId(), $response["application"]["id"]);
        $this->assertEquals("https://api-test.com", $response['dashboard_url']);
        $this->assertEquals($expectedRedirectUrl, $response['onboarding_url']);
    }

    public function testGetAuthorizeViewDataForNonPG()
    {
        $redirectUrl = 'https://test.com';

        config([
            'trace.services.splitz.mock'        => true,
            'trace.app.phantom_onboarding_url'  => $redirectUrl
       ]);

        $input = [
            'state'         => '123',
            'scope'         => 'rx_read_only',
            'client_id'     => '30000000000000',
            'redirect_uri'  => 'https://www.example.com',
            'response_type' => 'code'
        ];

        $this->createAndSetClientWithEnvironment();

        $service = new Service();
        $service->oauthServer = $this->oauthServerMock;

        $this->oauthServerMock
            ->shouldReceive('validateAuthorizeRequestAndGetScopes')
            ->andReturn(collect(
                [
                    new Scope\Entity(ScopeConstants::RX_READ_ONLY),
                ]));

        $response = $service->getAuthorizeViewData($input);

        $this->assertEquals("10000000000000", $response["application"]["merchant_id"]);
        $this->assertEquals($this->application->getId(), $response["application"]["id"]);
        $this->assertEquals("https://api-test.com", $response['dashboard_url']);
        $this->assertNull($response['onboarding_url']);
    }

    /**
     * @throws \Razorpay\OAuth\Exception\ServerException
     * @throws \App\Exception\LogicException
     * @throws \App\Exception\BadRequestException
     * @throws \Razorpay\OAuth\Exception\BadRequestException|\Throwable
     */
    public function testFetchCustomPolicyUrlForApplicationThrowsException()
    {
        $input = [
            'state'         => '123',
            'scope'         => 'read_only',
            'client_id'     => '30000000000000',
            'redirect_uri'  => 'https://www.example.com',
            'response_type' => 'code'
        ];

        $this->createAndSetClientWithEnvironment('dev', '20000000000000');

        $service = new Service();
        $service->oauthServer = $this->oauthServerMock;

        $this->oauthServerMock
            ->shouldReceive('validateAuthorizeRequestAndGetScopes')
            ->andReturn(collect(
                [
                    new Scope\Entity(ScopeConstants::READ_ONLY),
                ]));

        $response = $service->getAuthorizeViewData($input);

        $this->assertNull($response['custom_policy_url']);
    }

    /**
     * @throws \App\Exception\BadRequestException
     * @throws \Razorpay\OAuth\Exception\ServerException
     * @throws \App\Exception\LogicException
     * @throws \Razorpay\OAuth\Exception\BadRequestException|\Throwable
     */
    public function testFetchCustomPolicyUrlForApplicationWithUrlNotConfigured()
    {
        $input = [
            'state'         => '123',
            'scope'         => 'read_only',
            'client_id'     => '30000000000000',
            'redirect_uri'  => 'https://www.example.com',
            'response_type' => 'code'
        ];

        $this->createAndSetClientWithEnvironment('dev', '20000000000001');

        $service = new Service();
        $service->oauthServer = $this->oauthServerMock;

        $this->oauthServerMock
            ->shouldReceive('validateAuthorizeRequestAndGetScopes')
            ->andReturn(collect(
                [
                    new Scope\Entity(ScopeConstants::READ_ONLY),
                ]));

        $response = $service->getAuthorizeViewData($input);

        $this->assertNull($response['custom_policy_url']);
    }

    /**
     * @return void
     * @throws \Razorpay\OAuth\Exception\BadRequestException
     * @throws \Throwable
     */
    public function testGetAuthorizeViewDataForPhantomWithOnboardingSignature()
    {
        $redirectUrl = 'https://test.com';

        config([
                   'trace.services.splitz.mock'        => true,
                   'trace.app.phantom_onboarding_url'  => $redirectUrl
               ]);

        $input = [
            'state'         => '123',
            'scope'         => 'read_only',
            'client_id'     => '30000000000000',
            'redirect_uri'  => 'https://www.example.com',
            'response_type' => 'code',
            'onboarding_signature' => 'ajdhfuhfug'
        ];

        $this->createAndSetClientWithEnvironment();

        $expectedRedirectUrl = $redirectUrl . '?applicationId=' . $this->application->getId() . '&onboarding_signature=ajdhfuhfug';

        $service = new Service();
        $service->oauthServer = $this->oauthServerMock;

        $this->oauthServerMock
            ->shouldReceive('validateAuthorizeRequestAndGetScopes')
            ->andReturn(collect(
                            [
                                new Scope\Entity(ScopeConstants::READ_ONLY),
                            ]));

        $response = $service->getAuthorizeViewData($input);

        $this->assertEquals("10000000000000", $response["application"]["merchant_id"]);
        $this->assertEquals($this->application->getId(), $response["application"]["id"]);
        $this->assertEquals("https://api-test.com", $response['dashboard_url']);
        $this->assertEquals($expectedRedirectUrl, $response['onboarding_url']);
    }

    public function testGetAuthorizeViewDataWithPlatformFeeNotEnabled() : void
    {
        config(['trace.services.splitz.mock' => true]);

        $input = [
            'state'         => '123',
            'scope'         => 'read_only',
            'client_id'     => '30000000000000',
            'redirect_uri'  => 'https://www.example.com',
            'response_type' => 'code'
        ];

        $this->createAndSetClientWithEnvironment('dev', '20000000000002');

        $service = new Service();
        $service->oauthServer = $this->oauthServerMock;

        $this->oauthServerMock
            ->shouldReceive('validateAuthorizeRequestAndGetScopes')
            ->andReturn(collect(
                [
                    new Scope\Entity(ScopeConstants::READ_ONLY),
                ]));

        $response = $service->getAuthorizeViewData($input);

        $this->assertEquals("10000000000000", $response["application"]["merchant_id"]);
        $this->assertEquals($this->application->getId(), $response["application"]["id"]);
        $this->assertEquals("https://api-test.com", $response['dashboard_url']);
        $this->assertNotNull($response['custom_policy_url']);
        $this->assertNotNull($response['onboarding_url']);
        $this->assertFalse($response['is_platform_fee_enabled']);
    }
}
