<?php

namespace App\Tests\Unit\Http\Controllers;

use Mockery;
use Exception;
use ReflectionException;
use Mockery\MockInterface;
use OpenCensus\Core\Scope;
use Razorpay\OAuth\OAuthServer;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use Razorpay\OAuth\Scope\ScopeConstants;
use App\Tests\Unit\UnitTestCase as UnitTestCase;

class AuthControllerTest extends UnitTestCase
{
    const VALIDATION_FAILED = 'Validation failed. The state field is required.';

    private $authServiceMock;

    public function setUp(): Void
    {
        parent::setUp();
        $this->setAuthServiceMock(Mockery::mock('overload:App\Models\Auth\Service'));
    }

    public function tearDown(): Void
    {
        parent::tearDown();
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
    public function getAuthServiceMock()
    {
        return $this->authServiceMock;
    }

    /**
     * @Test '/'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getRoot should return 'Welcome to Razorpay Auth!'.
     * @return void
     */
    public function testGetRoot()
    {
        $expectedResponse = ['message' => 'Welcome to Razorpay Auth!'];
        $controller = new AuthController();
        $response = $controller->getRoot()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($expectedResponse), $response);
    }

    /**
     * @Test '/status'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getStatus should return '[ 'DB' => 'Ok']'.
     * @return void
     */
    public function testGetStatus()
    {
        $expectedResponse = ['DB' => 'Ok'];
        DB::shouldReceive('connection')
            ->once()
            ->with('auth')
            ->andReturn(Mockery::mock('Illuminate\Database\Connection', function ($mock) {
                $mock->shouldReceive('getPdo')
                    ->once()
                    ->andReturn(['DB' => 'Ok',]);
            })
            );

        $controller = new AuthController();
        $response = $controller->getStatus()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($expectedResponse), $response);
    }

    /**
     * @Test '/status'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getStatus should throw DB Error.
     * @return void
     */
    public function testGetStatusException()
    {
        $expectedResponse = ['error' => 'DB error'];
        DB::shouldReceive('connection')
            ->once()
            ->with('auth')
            ->andReturn(Mockery::mock('Illuminate\Database\Connection', function ($mock) {
                $mock->shouldReceive('getPdo')
                    ->once()
                    ->andThrow(new Exception());
            })
            );

        $controller = new AuthController();
        $response = $controller->getStatus()->getContent();
        $this->assertJsonStringEqualsJsonString(json_encode($expectedResponse), $response);
    }

    /**
     * @Test '/authorize'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getAuthorize should return ERROR VIEW('authorize_error') with Exception String.
     * @return void
     */
    public function testGetAuthorizeExceptionView(): void
    {
        $this->getAuthServiceMock()
            ->shouldReceive('getAuthorizeViewData')
            ->andThrow(new Exception(self::VALIDATION_FAILED));

        $controller = new AuthController();
        $response = $controller->getAuthorize();

        $partialResponse = $this->getErrorMessage(self::VALIDATION_FAILED);

        $this->assertEquals(AuthController::AUTHORIZE_ERROR_VIEW, $response->name());
        $this->assertEquals($response->getData(), $partialResponse);
    }

    /**
     * @Test '/authorize'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getAuthorize should return VIEW('authorize_pg') with Data.
     * @return void
     */
    public function testGetAuthorizeForPGAndX(): void
    {
        $mockResponseData = [
            'application'   =>
                [
                    'name' => 'Acme Corp',
                    'logo' => null
                ],
            'scope_names'        => [
                ScopeConstants::READ_ONLY,
            ],
            'scope_descriptions' => OAuthServer::$scopes[ScopeConstants::READ_ONLY],
            'dashboard_url' => 'https://example.com/',
            'query_params'  => ''
        ];

        $this->getAuthServiceMock()
             ->shouldReceive('getAuthorizeViewData')
             ->andReturn($mockResponseData);

        $controller = new AuthController();

        $response   = $controller->getAuthorize();

        $this->assertEquals(AuthController::AUTHORIZE_PG_X_VIEW, $response->name());

        $this->assertEquals($response['data'], $mockResponseData);
    }

    /**
     * @Test '/authorize'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getAuthorize should return VIEW('authorize') with Data.
     * @return void
     */
    public function testGetAuthorizeForDefault(): void
    {
        $mockResponseData = [
            'application'   =>
                [
                    'name' => 'Acme Corp',
                    'logo' => null
                ],
            'scope_names'        => [
                ScopeConstants::TALLY_READ_ONLY,
            ],
            'scope_descriptions' => OAuthServer::$scopes[ScopeConstants::TALLY_READ_ONLY],
            'dashboard_url' => 'https://example.com/',
            'query_params'  => ''
        ];

        $this->getAuthServiceMock()
             ->shouldReceive('getAuthorizeViewData')
             ->andReturn($mockResponseData);

        $controller = new AuthController();

        $response   = $controller->getAuthorize();

        $this->assertEquals(AuthController::AUTHORIZE_DEFAULT_VIEW, $response->name());

        $this->assertEquals($response['data'], $mockResponseData);
    }

    /**
     * @Test '/authorize'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * postAuthorize should Redirect to target url
     * @return void
     */
    public function testPostAuthorize()
    {
        $this->getAuthServiceMock()
            ->shouldReceive('postAuthCode');

        $controller = new AuthController();
        $response = $controller->postAuthorize();
        $this->assertTrue($response->isRedirect());
    }

    /**
     * @Test '/authorize'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * deleteAuthorize should Redirect to target url
     * @return void
     */
    public function testDeleteAuthorize()
    {
        $this->getAuthServiceMock()
            ->shouldReceive('postAuthCode');

        $controller = new AuthController();
        $response = $controller->deleteAuthorize();
        $this->assertTrue($response->isRedirect());
    }

    /**
     * @Test '/token'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * postAccessToken should return json response
     * @return void
     */
    public function testPostAccessToken()
    {
        $mockResponseData = [
            'public_token' => 'rzp_test_oauth_9xu1rkZqoXlClS',
            'token_type' => 'Bearer',
            'expires_in' => 7862400,
            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IkY1Z0NQYkhhRzRjcUpnIn0.eyJhdWQiOiJGNFNNeEgxanMxbkpPZiIsImp0aSI6IkY1Z0NQYkhhRzRjcUpnIiwiaWF0IjoxNTkyODMxMDExLCJuYmYiOjE1OTI4MzEwMTEsInN1YiI6IiIsImV4cCI6MTYwMDc3OTgxMSwidXNlcl9pZCI6IkYycVBpejJEdzRPRVFwIiwibWVyY2hhbnRfaWQiOiJGMnFQaVZ3N0lNV01GSyIsInNjb3BlcyI6WyJyZWFkX29ubHkiXX0.Wwqt5czhoWpVzP5_aoiymKXoGj-ydo-4A_X2jf_7rrSvk4pXdqzbA5BMrHxPdPbeFQWV6vsnsgbf99Q3g-W4kalHyH67LfAzc3qnJ-mkYDkFY93tkeG-MCco6GJW-Jm8xhaV9EPUak7z9J9jcdluu9rNXYMtd5qxD8auyRYhEgs',
            'refresh_token' => 'def50200f42e07aded65a323f6c53181d802cc797b62cc5e78dd8038d6dff253e5877da9ad32f463a4da0ad895e3de298cbce40e162202170e763754122a6cb97910a1f58e2378ee3492dc295e1525009cccc45635308cce8575bdf373606c453ebb5eb2bec062ca197ac23810cf9d6cf31fbb9fcf5b7d4de9bf524c89a4aa90599b0151c9e4e2fa08acb6d2fe17f30a6cfecdfd671f090787e821f844e5d36f5eacb7dfb33d91e83b18216ad0ebeba2bef7721e10d436c3984daafd8654ed881c581d6be0bdc9ebfaee0dc5f9374d7184d60aae5aa85385690220690e21bc93209fb8a8cc25a6abf1108d8277f7c3d38217b47744d7',
            'razorpay_account_id' => 'acc_Dhk2qDbmu6FwZH'
        ];

        $this->getAuthServiceMock()
            ->shouldReceive('generateAccessToken')
            ->andReturn($mockResponseData);

        $controller = new AuthController();
        $response = $controller->postAccessToken()->getContent();
        $this->assertEquals(json_encode($mockResponseData), $response);
    }

    /**
     * @Test '/tokens/internal'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * createPartnerToken should return json response
     * @return void
     */
    public function testCreatePartnerToken()
    {
        $this->getAuthServiceMock()
            ->shouldReceive('postAuthCodeAndGenerateAccessToken')
            ->andReturn('Token');

        $controller = new AuthController();
        $response = $controller->createPartnerToken()->getOriginalContent();
        $this->assertEquals('Token', $response);
    }

    /**
     * @Test '/authorize/tally'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * postTallyAuthorize should return json response
     * @return void
     */
    public function testPostTallyAuthorize()
    {
        $mockedResponse = ['success' => true];
        $this->getAuthServiceMock()
            ->shouldReceive('validateTallyUserAndSendOtp')
            ->andReturn($mockedResponse);

        $controller = new AuthController();
        $response = $controller->postTallyAuthorize()->getContent();
        $this->assertEquals(json_encode($mockedResponse), $response);
    }

    /**
     * @Test '/tokens/tally'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * postCreateTallyAuthorize should return json response
     * @return void
     */
    public function testPostCreateTallyToken()
    {
        $mockResponseData = [
            'public_token' => 'rzp_test_oauth_9xu1rkZqoXlClS',
            'token_type' => 'Bearer',
            'expires_in' => 7862400,
            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IkY1Z0NQYkhhRzRjcUpnIn0.eyJhdWQiOiJGNFNNeEgxanMxbkpPZiIsImp0aSI6IkY1Z0NQYkhhRzRjcUpnIiwiaWF0IjoxNTkyODMxMDExLCJuYmYiOjE1OTI4MzEwMTEsInN1YiI6IiIsImV4cCI6MTYwMDc3OTgxMSwidXNlcl9pZCI6IkYycVBpejJEdzRPRVFwIiwibWVyY2hhbnRfaWQiOiJGMnFQaVZ3N0lNV01GSyIsInNjb3BlcyI6WyJyZWFkX29ubHkiXX0.Wwqt5czhoWpVzP5_aoiymKXoGj-ydo-4A_X2jf_7rrSvk4pXdqzbA5BMrHxPdPbeFQWV6vsnsgbf99Q3g-W4kalHyH67LfAzc3qnJ-mkYDkFY93tkeG-MCco6GJW-Jm8xhaV9EPUak7z9J9jcdluu9rNXYMtd5qxD8auyRYhEgs',
            'refresh_token' => 'def50200f42e07aded65a323f6c53181d802cc797b62cc5e78dd8038d6dff253e5877da9ad32f463a4da0ad895e3de298cbce40e162202170e763754122a6cb97910a1f58e2378ee3492dc295e1525009cccc45635308cce8575bdf373606c453ebb5eb2bec062ca197ac23810cf9d6cf31fbb9fcf5b7d4de9bf524c89a4aa90599b0151c9e4e2fa08acb6d2fe17f30a6cfecdfd671f090787e821f844e5d36f5eacb7dfb33d91e83b18216ad0ebeba2bef7721e10d436c3984daafd8654ed881c581d6be0bdc9ebfaee0dc5f9374d7184d60aae5aa85385690220690e21bc93209fb8a8cc25a6abf1108d8277f7c3d38217b47744d7',
            'razorpay_account_id' => 'acc_Dhk2qDbmu6FwZH'
        ];
        $this->getAuthServiceMock()
            ->shouldReceive('generateTallyAccessToken')
            ->andReturn($mockResponseData);

        $controller = new AuthController();
        $response = $controller->createTallyToken()->getContent();

        $this->assertEquals(json_encode($mockResponseData), $response);
    }

    /**
     * @Test '/authorize-multi-token'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * postAuthorizeMultiToken should Redirect to target url
     * @return void
     */
    public function testPostAuthorizeMultiToken()
    {
        $this->getAuthServiceMock()
            ->shouldReceive('postAuthCodeMultiToken');

        $controller = new AuthController();
        $response = $controller->postAuthorizeMultiToken();

        $this->assertTrue($response->isRedirect());
    }

    /**
     * @Test '/authorize-multi-token'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getAuthorizeMultiToken should Return VIEW('authorize_multi_token') with data
     * @return void
     */
    public function testGetAuthorizeMultiToken(): void
    {
        $mockResponseData = [
            'application' =>
                [
                    'name' => 'Acme Corp',
                    'logo' => null
                ],
            'scope_names'        => [
                ScopeConstants::READ_ONLY,
            ],
            'scope_descriptions' => OAuthServer::$scopes[ScopeConstants::READ_ONLY],
            'dashboard_url' => 'https://example.com/',
            'query_params' => ''
        ];

        $this->getAuthServiceMock()
            ->shouldReceive('getAuthorizeMultiTokenViewData')
            ->andReturn($mockResponseData);

        $controller = new AuthController();
        $response = $controller->getAuthorizeMultiToken();

        $this->assertEquals(AuthController::AUTHORIZE_MULTI_TOKEN_VIEW, $response->name());

        $this->assertEquals($response->getData()['data'], $mockResponseData);
    }

    /**
     * @Test '/authorize-multi-token'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getAuthorizeMultiToken should Return VIEW('authorize_error') with data
     * @return void
     */
    public function testGetAuthorizeMultiTokenWithException(): void
    {
        $this->getAuthServiceMock()
            ->shouldReceive('getAuthorizeMultiTokenViewData')
            ->andThrow(new Exception(self::VALIDATION_FAILED));

        $controller = new AuthController();
        $response = $controller->getAuthorizeMultiToken();

        $partialResponse = $this->getErrorMessage(self::VALIDATION_FAILED);

        $this->assertEquals(AuthController::AUTHORIZE_ERROR_VIEW, $response->name());

        $this->assertEquals($response->getData(), $partialResponse);
    }

    /**
     * @Test '/authorize-multi-token'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * deleteAuthorizeMultiToken should Redirect to target url
     * @return void
     */
    public function testDeleteAuthorizeMultiToken()
    {
        $this->getAuthServiceMock()
            ->shouldReceive('postAuthCodeMultiToken');

        $controller = new AuthController();
        $response = $controller->deleteAuthorizeMultiToken();

        $this->assertTrue($response->isRedirect());
    }

    /**
     * @return string[][]
     */
    public function getErrorMessage($message)
    {
        return ['error' => ['message' => $message]];
    }

    /**
     * Tests the scope to view map
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws ReflectionException
     */
    public function testGetViewForScope()
    {
        $class = new \ReflectionClass('App\Http\Controllers\AuthController');

        $method = $class->getMethod('getViewForScope');

        // test for PG/X scopes
        $response = $method->invokeArgs(new AuthController(), [[ScopeConstants::READ_ONLY]]);
        $this->assertEquals(AuthController::AUTHORIZE_PG_X_VIEW, $response);

        $response = $method->invokeArgs(new AuthController(), [[ScopeConstants::READ_WRITE]]);
        $this->assertEquals(AuthController::AUTHORIZE_PG_X_VIEW, $response);

        $response = $method->invokeArgs(new AuthController(), [[ScopeConstants::RX_READ_ONLY]]);
        $this->assertEquals(AuthController::AUTHORIZE_PG_X_VIEW, $response);

        // test for non PG/X scopes
        $response = $method->invokeArgs(new AuthController(), [[ScopeConstants::TALLY_READ_ONLY]]);
        $this->assertEquals(AuthController::AUTHORIZE_DEFAULT_VIEW, $response);

        // test for unmapped scopes
        $response = $method->invokeArgs(new AuthController(), [["unmapped_scope"]]);
        $this->assertEquals(AuthController::AUTHORIZE_DEFAULT_VIEW, $response);

        // test that the view corresponding to the first scope is picked if multiple scopes are passed
        $response = $method->invokeArgs(new AuthController(), [[ScopeConstants::READ_WRITE, ScopeConstants::RX_READ_WRITE]]);
        $this->assertEquals(AuthController::AUTHORIZE_PG_X_VIEW, $response);

        $response = $method->invokeArgs(new AuthController(), [[ScopeConstants::TALLY_READ_ONLY, ScopeConstants::READ_WRITE]]);
        $this->assertEquals(AuthController::AUTHORIZE_DEFAULT_VIEW, $response);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testViewExists(): void
    {
        $class = new \ReflectionClass('App\Http\Controllers\AuthController');

        $scopeToViewMap = $class->getConstant("SCOPE_TO_VIEW_MAP");

        foreach ($scopeToViewMap as $viewName)
        {
            $view = view($viewName);
            $this->assertEquals($viewName, $view->getname());
        }
    }
}
