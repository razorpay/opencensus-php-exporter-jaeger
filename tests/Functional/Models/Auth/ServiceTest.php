<?php

namespace Functional\Models\Auth;

use App\Tests\TestCase;
use App\Models\Auth\Service;
use Razorpay\OAuth\Scope;
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
        $this->assertNull($response['onboarding_url']);
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
}
