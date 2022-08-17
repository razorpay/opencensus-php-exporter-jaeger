<?php

namespace App\Tests\Unit\Http\Controllers;

use App\Constants\TraceCode;
use App\Exception\LogicException;
use App\Http\Controllers\TokenController;
use App\Tests\Unit\UnitTestCase as UnitTestCase;
use Illuminate\Support\Facades\Request as RequestFacade;
use Mockery;
use Mockery\MockInterface;
use Razorpay\OAuth\Application\Entity as ApplicationEntity;
use Razorpay\OAuth\Client\Entity as ClientEntity;
use Razorpay\OAuth\Token\Entity;
use Razorpay\Trace\Facades\Trace;

class TokenControllerTest extends UnitTestCase
{
    const MERCHANT_ID = '10000000000000';
    const SUB_MERCHANT_ID = '40000000000000';
    const APPLICATION_ID = '10000000000000';
    const USER_ID = '20000000000000';
    const TOKEN_ID = '30000000000000';
    const ENV = 'dev';
    const PUBLIC_TOKEN = 'rzp_live_oauth_uoNZbPKwagaQR9';
    const WRONG_PUBLIC_TOKEN = 'rzp_oauth_uoNZbPKwagaQR9';

    private $apiServiceMock;
    private $authServiceMock;
    private $authRepositoryMock;
    private $razorpayOauthTokenServiceMock;
    private $razorpayOauthTokenRepositoryMock;
    private $razorpayOauthRefreshTokenServiceMock;
    private $tokenServiceMock;

    public function setUp(): Void
    {
        parent::setUp();
//        $this->setAuthServiceMock(Mockery::mock('overload:App\Models\Auth\Service'));
//        $this->setRazorpayOauthTokenServiceMock(Mockery::mock('overload:Razorpay\OAuth\Token\Service'));
//        $this->setRazorpayOauthRefreshTokenServiceMock(Mockery::mock('overload:Razorpay\OAuth\RefreshToken\Service'));
//        $this->setTokenServiceMock(Mockery::mock('overload:App\Models\Token\Service'));
//        $this->setAuthRepositoryMock(Mockery::mock('overload:App\Models\Auth\Repository'));
//        $this->setRazorpayOauthTokenRepositoryMock(Mockery::mock('overload:Razorpay\OAuth\Token\Repository'));
//        $this->setApiServiceMock(Mockery::mock('overload:App\Services\Api'));
    }

    public function tearDown(): Void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @return MockInterface
     */
    public function getApiServiceMock()
    {
        return $this->apiServiceMock;
    }

    /**
     * @param mixed $apiServiceMock
     */
    public function setApiServiceMock($apiServiceMock)
    {
        $this->apiServiceMock = $apiServiceMock;
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
     * @return MockInterface
     */
    public function getAuthRepositoryMock()
    {
        return $this->authRepositoryMock;
    }

    /**
     * @param mixed $authRepositoryMock
     */
    public function setAuthRepositoryMock($authRepositoryMock)
    {
        $this->authRepositoryMock = $authRepositoryMock;
    }

    /**
     * @return MockInterface
     */
    public function getAuthServiceMock()
    {
        return $this->authServiceMock;
    }

    /**
     * @param mixed $authServiceMock
     */
    public function setAuthServiceMock($authServiceMock)
    {
        $this->authServiceMock = $authServiceMock;
    }

    /**
     * @return MockInterface
     */
    public function getRazorpayOauthTokenServiceMock()
    {
        return $this->razorpayOauthTokenServiceMock;
    }

    /**
     * @param mixed $razorpayOauthTokenServiceMock
     */
    public function setRazorpayOauthTokenServiceMock($razorpayOauthTokenServiceMock)
    {
        $this->razorpayOauthTokenServiceMock = $razorpayOauthTokenServiceMock;
    }

    /**
     * @return MockInterface
     */
    public function getRazorpayOauthRefreshTokenServiceMock()
    {
        return $this->razorpayOauthRefreshTokenServiceMock;
    }

    /**
     * @param mixed $razorpayOauthRefreshTokenServiceMock
     */
    public function setRazorpayOauthRefreshTokenServiceMock($razorpayOauthRefreshTokenServiceMock)
    {
        $this->razorpayOauthRefreshTokenServiceMock = $razorpayOauthRefreshTokenServiceMock;
    }

    /**
     * @return MockInterface
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
     * @Test '/tokens/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * get should return Token with ID.
     * @return void
     */
    public function testGetTokenById()
    {
        $tokenId = self::TOKEN_ID;
        $partialResponse = [
            'merchant_id' => self::MERCHANT_ID,
            'user_id' => self::USER_ID,
            'type' => 'access_token',
        ];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_TOKEN_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getRazorpayOauthTokenServiceMock()
            ->shouldReceive('getToken')
            ->withArgs([$tokenId, Mockery::any()])
            ->andReturn($partialResponse);

        $controller = new TokenController();
        $response = $controller->get($tokenId)->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/public_tokens/{id}/validate'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * validatePublicToken should validate token by ID.
     * @return void
     * @throws \App\Exception\LogicException
     */
    public function testValidatePublicTokenWhenExists()
    {
        $id = self::PUBLIC_TOKEN;
        $partialResponse = ['exist' => true];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::VALIDATE_PUBLIC_TOKEN_REQUEST, ['id' => $id]])
            ->once();
        $this->getAuthRepositoryMock()
            ->shouldReceive('findByPublicTokenIdAndMode')
            ->andReturn('dummy_token');

        $controller = new TokenController();
        $response = $controller->validatePublicToken($id)->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/public_tokens/{id}/validate'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * validatePublicToken should validate token by ID.
     * @return void
     * @throws LogicException
     */
    public function testValidatePublicTokenWhenNotExists()
    {
        $id = self::PUBLIC_TOKEN;
        $partialResponse = ['exist' => false];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::VALIDATE_PUBLIC_TOKEN_REQUEST, ['id' => $id]])
            ->once();
        $this->getAuthRepositoryMock()
            ->shouldReceive('findByPublicTokenIdAndMode')
            ->andReturn(null);

        $controller = new TokenController();
        $response = $controller->validatePublicToken($id)->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/public_tokens/{id}/validate'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * validatePublicToken should validate token by ID.
     * @return void
     */
    public function testValidatePublicTokenWhenInvalid()
    {
        $id = self::WRONG_PUBLIC_TOKEN;
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::VALIDATE_PUBLIC_TOKEN_REQUEST, ['id' => $id]])
            ->once();

        try {
            $controller = new TokenController();
            $controller->validatePublicToken($id);
        } catch (LogicException $e) {
            $this->assertEquals('public token is invalid', $e->getMessage());
        }
    }

    /**
     * @Test '/tokens'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getAll should return All Tokens for merchant.
     * @return void
     */
    public function testGetTokens()
    {
        $mockedResponse = [
            'entity' => 'collection',
            'count' => 1,
            'admin' => true,
            'items' => [
                [
                    'public_token' => 'rzp_test_oauth_9xu1rkZqoXlClS',
                    'token_type' => 'Bearer',
                    'expires_in' => 7862400,
                    'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6Ijl4dTF',
                    'refresh_token' => 'def5020096e1c470c901d34cd60fa53abdaf36620e823ffa53'
                ]
            ]
        ];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_TOKENS_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getRazorpayOauthTokenServiceMock()
            ->shouldReceive('getAllTokens')
            ->andReturn($mockedResponse);

        $controller = new TokenController();
        $response = $controller->getAll()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($mockedResponse), $response);
    }

    /**
     * @Test '/tokens/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * revokeToken should delete old tokens.
     * @return void
     */
    public function testRevokeToken()
    {
        $application = new ApplicationEntity();
        $devClient = new ClientEntity();
        $devClient->fill([
            'id' => self::TOKEN_ID,
            'application_id' => $application->id,
            'redirect_url' => ['https://www.example.com'],
            'environment' => self::ENV
        ]);
        $token = new Entity();
        $token->setClient($devClient);

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('findOrFailPublic')
            ->withArgs(['tokenId'])
            ->andReturn($token);

        $this->getRazorpayOauthTokenRepositoryMock()
            ->shouldReceive('fetchAccessTokensByAppAndMerchant')
            ->withArgs([$application->id, self::APPLICATION_ID])
            ->andReturn([]);

        $this->getRazorpayOauthTokenServiceMock()
            ->shouldReceive('revoketoken')
            ->withArgs(['tokenId', Mockery::any()])
            ->andReturn([]);

        $this->getAuthServiceMock()
            ->shouldReceive('getApiService')
            ->andReturn(Mockery::mock('overload:App\Services\Api', function ($mock) {
                $mock->shouldReceive('revokeMerchantApplicationMapping')
                    ->once()
                    ->andReturn([]);
            }));

        $this->getApiServiceMock()
            ->shouldReceive('revokeMerchantApplicationMapping')
            ->withArgs([$application->id, self::APPLICATION_ID])
            ->andReturn();

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REVOKE_TOKEN_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn(['merchant_id' => self::MERCHANT_ID]);

        $controller = new TokenController();
        $response = $controller->revoke('tokenId')->getContent();

        $this->assertJsonStringEqualsJsonString('[]', $response);
    }

    /**
     * @Test '/tokens/partner'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * createForPartner should create partner and return created response.
     * @return void
     */
    public function testCreateForPartner()
    {

        $partialResponse = [
            'merchant_id' => self::MERCHANT_ID,
            'user_id' => self::USER_ID,
            'type' => 'access_token',
        ];
        $this->getRazorpayOauthTokenServiceMock()
            ->shouldReceive('createPartnerToken')
            ->withArgs([self::APPLICATION_ID, self::MERCHANT_ID, self::SUB_MERCHANT_ID])
            ->andReturn($partialResponse);
        RequestFacade::shouldReceive('all')->andReturn([
            'application_id' => self::APPLICATION_ID,
            'partner_merchant_id' => self::MERCHANT_ID,
            'sub_merchant_id' => self::SUB_MERCHANT_ID,
        ]);

        $controller = new TokenController();
        $response = $controller->createForPartner()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/revoke'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * revokeByPartner should revoke token.
     * @return void
     */
    public function testRevokeForPartner()
    {
        $this->getTokenServiceMock()
            ->shouldReceive('handleRevokeTokenRequest');
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::REVOKE_TOKEN_BY_PARTNER])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);

        $controller = new TokenController();
        $response = $controller->revokeByPartner()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode(['message' => 'Token Revoked']), $response);
    }

    public function testRevokeAccessTokenForMobileApp()
    {
        $tokenController = new TokenController();

        $tokens = [
            'items' => [
                ['type' => 'refresh_token']
            ]
        ];

        $this->assertNull($tokenController->revokeAccessTokensForMobile($tokens));
    }

}
