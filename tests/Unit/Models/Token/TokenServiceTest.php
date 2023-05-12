<?php

namespace Unit\Models\Admin;

use Mockery\MockInterface;
use App\Constants\TraceCode;
use App\Models\Token\Service;
use App\Constants\RequestParams;
use App\Tests\Unit\UnitTestCase;
use Razorpay\OAuth\Token\Entity;
use Razorpay\Trace\Facades\Trace;
use Razorpay\OAuth\Base\PublicCollection;

class TokenServiceTest extends UnitTestCase
{
    const MERCHANT_ID = '10000000000000';
    const APPLICATION_ID = '10000000000000';
    const TOKEN_ID = '30000000000000';

    protected $oauthTokenService;
    protected $oauthRefreshTokenService;
    protected $razorpayOauthTokenRepositoryMock;
    private $authServiceMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setOauthTokenService(\Mockery::mock('overload:Razorpay\OAuth\Token\Service'));
        $this->setAuthServiceMock(\Mockery::mock('overload:App\Models\Auth\Service'));
        $this->setOauthRefreshTokenService(\Mockery::mock('overload:Razorpay\OAuth\RefreshToken\Service'));
        $this->setRazorpayOauthTokenRepositoryMock(\Mockery::mock('overload:Razorpay\OAuth\Token\Repository'));
    }


    /**
     * @return MockInterface
     */
    public function getOauthTokenService()
    {
        return $this->oauthTokenService;
    }

    /**
     * @param mixed $oauthTokenService
     */
    public function setOauthTokenService($oauthTokenService)
    {
        $this->oauthTokenService = $oauthTokenService;
    }

    /**
     * @param mixed $authServiceMock
     */
    public function setAuthServiceMock($authServiceMock)
    {
        $this->authServiceMock = $authServiceMock;
    }

    public function getAuthServiceMock()
    {
        return $this->authServiceMock;
    }

    /**
     * @return MockInterface
     */
    public function getOauthRefreshTokenService()
    {
        return $this->oauthRefreshTokenService;
    }

    /**
     * @param mixed $oauthRefreshTokenService
     */
    public function setOauthRefreshTokenService($oauthRefreshTokenService)
    {
        $this->oauthRefreshTokenService = $oauthRefreshTokenService;
    }

    /**
     * @return MockInterface
     */
    public function getRazorpayOauthTokenRepositoryMock()
    {
        return $this->razorpayOauthTokenRepositoryMock;
    }

    /**
     * @param mixed $razorpayOauthTokenRepositoryMock
     */
    public function setRazorpayOauthTokenRepositoryMock($razorpayOauthTokenRepositoryMock)
    {
        $this->razorpayOauthTokenRepositoryMock = $razorpayOauthTokenRepositoryMock;
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * handleRevokeTokenRequestForAccessToken should call revokeAccessToken from oauthTokenService.
     * @return void
     */
    public function testHandleRevokeTokenRequestForAccessToken()
    {
        $input = [
            RequestParams::CLIENT_ID => 'abcd',
            RequestParams::CLIENT_SECRET => 'abcd',
            RequestParams::TOKEN => 'abcd',
            RequestParams::TOKEN_TYPE_HINT => 'access_token',
        ];

        $this->getOauthTokenService()
            ->shouldReceive('revokeAccessTokenAfterValidation')
            ->once()
            ->with($input);

        $service = new Service();
        $service->handleRevokeTokenRequest($input);
    }

    /**
     * @Test '/tokens/submerchant/revoke_for_application/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * revokeSubmerchantTokensForApplication should delete all tokens along with submerchant-application mapping
     * @return void
     */
    public function testRevokeApplicationAccess()
    {
        $input = ['merchant_id' => self::MERCHANT_ID];
        $token = new Entity();
        $token->setAttribute("id", self::TOKEN_ID);

        $fetchTokenMockedResponse = PublicCollection::make([$token]);

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('fetchTokenIdsByMerchantAndApp')
            ->withArgs([self::APPLICATION_ID, self::MERCHANT_ID])
            ->andReturn($fetchTokenMockedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REVOKE_TOKENS_REQUEST, \Mockery::any()])
            ->once();

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('beginTransaction');

        $this->getOauthTokenService()
            ->shouldReceive('revokeAccessToken')
            ->withArgs([self::TOKEN_ID, $input])
            ->andReturn([])
            ->once();

        $this->getAuthServiceMock()
            ->shouldReceive('getApiService')
            ->andReturn(\Mockery::mock('overload:App\Services\Api', function ($mock) {
                $mock->shouldReceive('revokeMerchantApplicationMapping')
                    ->once()
                    ->withArgs([self::APPLICATION_ID, self::MERCHANT_ID])
                    ->andReturn(true);
            }));

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('commit');

        $service = new Service();
        $response = $service->revokeApplicationAccess(self::APPLICATION_ID, $input);

        $this->assertEquals(true, $response);
    }

    /**
     * @Test '/tokens/submerchant/revoke_for_application/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * revokeSubmerchantTokensForApplication should give an error when subm-app mapping deletion API fails
     * @return void
     */
    public function testRevokeApplicationAccessWhileAccessMapDeleteFailed()
    {
        $input = ['merchant_id' => self::MERCHANT_ID];
        $token = new Entity();
        $token->setAttribute("id", self::TOKEN_ID);

        $fetchTokenMockedResponse = PublicCollection::make([$token]);

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('fetchTokenIdsByMerchantAndApp')
            ->withArgs([self::APPLICATION_ID, self::MERCHANT_ID])
            ->andReturn($fetchTokenMockedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REVOKE_TOKENS_REQUEST, \Mockery::any()])
            ->once();

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('beginTransaction');

        $this->getOauthTokenService()
            ->shouldReceive('revokeAccessToken')
            ->withArgs([self::TOKEN_ID, $input])
            ->andReturn([])
            ->once();

        $this->getAuthServiceMock()
            ->shouldReceive('getApiService')
            ->andReturn(\Mockery::mock('overload:App\Services\Api', function ($mock) {
                $mock->shouldReceive('revokeMerchantApplicationMapping')
                    ->once()
                    ->withArgs([self::APPLICATION_ID, self::MERCHANT_ID])
                    ->andReturn(false);
            }));

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('rollback');

        $service = new Service();
        $response = $service->revokeApplicationAccess(self::APPLICATION_ID, $input);

        $this->assertEquals(false, $response);
    }

    /**
     * @Test '/tokens/submerchant/revoke_for_application/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * revokeSubmerchantTokensForApplication should return an error when token revoke step fails
     * @return void
     */
    public function testRevokeApplicationAccessWhileTokenRevokeFailed()
    {
        $input = ['merchant_id' => self::MERCHANT_ID];
        $token = new Entity();
        $token->setAttribute("id", self::TOKEN_ID);

        $fetchTokenMockedResponse = PublicCollection::make([$token]);

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('fetchTokenIdsByMerchantAndApp')
            ->withArgs([self::APPLICATION_ID, self::MERCHANT_ID])
            ->andReturn($fetchTokenMockedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REVOKE_TOKENS_REQUEST, \Mockery::any()])
            ->once();

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('beginTransaction');

        $this->getOauthTokenService()
            ->shouldReceive('revokeAccessToken')
            ->withArgs([self::TOKEN_ID, $input])
            ->andThrow(new \Exception('Some error'));

        Trace::shouldReceive('critical')
            ->withArgs([TraceCode::REVOKE_TOKEN_FAILED, \Mockery::any()])
            ->once();

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('rollback');

        $this->getAuthServiceMock()
            ->shouldReceive('getApiService')
            ->andReturn(\Mockery::mock('overload:App\Services\Api', function ($mock) {
                $mock->shouldReceive('revokeMerchantApplicationMapping')
                    ->never();
            }));

        $service = new Service();
        $response = $service->revokeApplicationAccess(self::APPLICATION_ID, $input);

        $this->assertEquals(false, $response);
    }

    /**
     * @Test '/tokens/submerchant/revoke_for_application/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * revokeSubmerchantTokensForApplication should return an error when we there are no active tokens
     * for the merchant-application pair.
     *
     * @return void
     */
    public function testRevokeApplicationAccessWithNoActiveToken()
    {
        $input = ['merchant_id' => self::MERCHANT_ID];

        $fetchTokenMockedResponse = PublicCollection::make([]);

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('fetchTokenIdsByMerchantAndApp')
            ->withArgs([self::APPLICATION_ID, self::MERCHANT_ID])
            ->andReturn($fetchTokenMockedResponse);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REVOKE_TOKENS_REQUEST, \Mockery::any()])
            ->once();

        try
        {
            $service = new Service();
            $service->revokeApplicationAccess(self::APPLICATION_ID, $input);
        }
        catch (\Exception $ex)
        {
            $this->assertEquals("This application doesn't have any access of the merchant", $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * handleRevokeTokenRequestForRefreshToken should call revokeRefreshToken from oauthRefreshTokenService.
     * @return void
     */
    public function testHandleRevokeTokenRequestForRefreshToken()
    {
        $input = [
            RequestParams::CLIENT_ID => 'IgRjoYW8GdwXFq',
            RequestParams::CLIENT_SECRET => 'x18rVuqh7AvWT5wnNZxTik09',
            RequestParams::TOKEN => 'def50200d71a8f8aea1dfb46b44d1572337e37a037c58cb0b2561b23171befbeaae4cfabcd172dc5ba2d9706404405e987b92ed6b9784dd45337b0b7cb5f39c1a414e58cb05059f5bf84807ec8b766dd0d58b498cb75dc16514b24b186830c61b0e8818b3ef952e240cf1a28d1d25dbefca4972daa8ea81a66517436e42cd9a4b71f2f64e0892fa8a480c57866b58582555043f9f7d3214938158b04f4e4d406d1b7198f7c83f5dc025818e0ed7fba68330cb73aa47063558937f7f7dd5f3d1e29c0b56ba3625e48ed0287f3efbee0ba2be757ae78020c26ff75c1cdd64d7a48f018cb8fa76ce4dfbc2d4af57c5455dd40fba07cf0e2',
            RequestParams::TOKEN_TYPE_HINT => 'refresh_token',
        ];

        $this->getOauthRefreshTokenService()
            ->shouldReceive('revokeRefreshToken')
            ->once()
            ->with($input);

        $service = new Service();
        $service->handleRevokeTokenRequest($input);
    }
}
