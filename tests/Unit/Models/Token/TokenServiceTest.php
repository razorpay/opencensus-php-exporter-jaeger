<?php

namespace Unit\Models\Admin;

use App\Constants\RequestParams;
use App\Models\Token\Service;
use App\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

class TokenServiceTest extends UnitTestCase
{

    protected $oauthTokenService;
    protected $oauthRefreshTokenService;

    public function setUp(): void
    {
        parent::setUp();
        $this->setOauthTokenService(\Mockery::mock('overload:Razorpay\OAuth\Token\Service'));
        $this->setOauthRefreshTokenService(\Mockery::mock('overload:Razorpay\OAuth\RefreshToken\Service'));
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
