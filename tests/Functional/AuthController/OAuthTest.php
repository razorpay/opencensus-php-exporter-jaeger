<?php

namespace App\Tests\Functional\AuthController;

use DB;
use Request;

use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use App\Tests\TestCase as TestCase;
use League\OAuth2\Server\CryptTrait;
use App\Tests\Concerns\RequestResponseFlowTrait;

class OAuthTest extends TestCase
{
    use RequestResponseFlowTrait;
    use CryptTrait;
    use UtilityTrait;

    /**
     * @var Client\Entity
     */
    protected $devClient;

    /**
     * @var Application\Entity
     */
    protected $application;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/OAuthTestData.php';

        parent::setup();
    }

    public function testGetRoot()
    {
        $this->startTest();
    }

    public function testGetStatus()
    {
        $this->startTest();
    }

    public function testGetAuthorizeUrl()
    {
        $data = $this->testData[$this->getName()];

        $response = $this->sendRequest($data['request']);

        $expectedString = 'Client authentication failed';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testGetAuthorizeUrlWithClientWithNonPGScope()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        $devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data = [
            'method' => 'get',
            'url'    => '/authorize?response_type=code' .
                '&client_id=' . $devClient->getId() .
                '&redirect_uri=https://www.example.com' .
                '&scope=tally_read_only' .
                '&state=123',
        ];

        $response = $this->sendRequest($data);

        $expectedString = 'Allow <span class="emphasis">' .
            $application->getName() .
            '</span> to access your <span class="emphasis merchant-name"></span> account on Razorpay?';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testGetAuthorizeUrlWithClientWithPGAndXScope()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        $devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data = [
            'method' => 'get',
            'url'    => '/authorize?response_type=code' .
                        '&client_id=' . $devClient->getId() .
                        '&redirect_uri=https://www.example.com' .
                        '&scope=read_only' .
                        '&state=123',
        ];

        $response = $this->sendRequest($data);

        $expectedString = '<p class="access-heading">'.
                          $application->getName() .
                          ' wants access to your Razorpay Account</p>';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());

        $platformFeeCondition = 'You are also authorizing Razorpay to deduct merchant services fee for each transaction as per terms specified
              for ' . $application->getName() . ' <a class="underline" href=https://www.xyz.com/terms target="_blank">here</a>';

        $this->assertStringContainsString($platformFeeCondition, $response->getContent());

        $data = [
            'method' => 'get',
            'url'    => '/authorize?response_type=code' .
                        '&client_id=' . $devClient->getId() .
                        '&redirect_uri=https://www.example.com' .
                        '&scope=rx_read_only' .
                        '&state=123',
        ];

        $response = $this->sendRequest($data);

        $expectedString = '<p class="access-heading">'.
                          $application->getName() .
                          ' wants access to your Razorpay Account</p>';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testGetAuthorizeUrlWithClientWithXScope()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        $devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data = [
            'method' => 'get',
            'url'    => '/authorize?response_type=code' .
                '&client_id=' . $devClient->getId() .
                '&redirect_uri=https://www.example.com' .
                '&scope=rx_read_only' .
                '&state=123',
        ];

        $response = $this->sendRequest($data);

        $expectedString = '<p class="access-heading">'.
            $application->getName() .
            ' wants access to your Razorpay Account</p>';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());

        $platformFeeCondition = 'You are also authorizing Razorpay to deduct merchant services fee for each transaction as per terms specified
              for ' . $application->getName() . ' <a class="underline" href=https://www.xyz.com/terms target="_blank">here</a>';

        $this->assertStringNotContainsString($platformFeeCondition, $response->getContent());
    }

    public function testGetAuthorizeUrlNoStateParam()
    {
        $data = $this->testData[$this->getName()];

        $response = $this->sendRequest($data['request']);

        $expectedString = 'Validation failed. The state field is required.';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testPostAuthCode()
    {
        $data = & $this->testData[__FUNCTION__];

        $application = Application\Entity::factory()->create();

       Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

       Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $response = $this->sendRequest($data['request']);

        $content = urldecode($response->getContent());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('http://localhost?code=', $content);
    }

    public function testPostAuthCodeInvalidToken()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $this->startTest();
    }

    public function testPostAuthCodeES256()
    {
        $data = & $this->testData[__FUNCTION__];

        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $response = $this->sendRequest($data['request']);

        $content = urldecode($response->getContent());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('http://localhost?code=', $content);
    }

    public function testPostAuthCodeInvalidRole()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $this->startTest();
    }

    public function testPostAuthCodeWithInvalidMerchantId()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->getId(), 'environment' => 'prod']);

        Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $this->startTest();
    }

    public function testPostAuthCodeWithReject()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->id, 'environment' => 'prod']);

        Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $content = urldecode($response->getContent());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('error=access_denied', $content);
    }

    public function testPostAccessToken()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertValidAccessToken($content);
    }

    public function testPostAccessTokenForES256()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'http://localhost',
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertValidAccessToken($content);
    }

    public function testPostRefreshToken()
    {
        $accessTokenEntity = json_decode($this->generateAccessTokenAndClearResolvedInstances());

        $data = &$this->testData[__FUNCTION__];

        $params = [
            'client_secret'   => $this->devClient->getSecret(),
            'refresh_token'   => $accessTokenEntity ->refresh_token ,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertValidAccessToken($content);
    }

    public function testPostRefreshTokenWithInvalidClientSecret()
    {
        $accessTokenEntity = json_decode($this->generateAccessTokenAndClearResolvedInstances());

        $data = &$this->testData[__FUNCTION__];

        $params = [
            'client_secret'   => "x18rVuqh7AvWT5wnNZxTik09",
            'refresh_token'   => $accessTokenEntity ->refresh_token ,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testPostRefreshTokenWithMissingRefreshToken()
    {
        $this->generateAccessTokenAndClearResolvedInstances();

        $data = &$this->testData[__FUNCTION__];

        $params = [
            'client_secret' => "x18rVuqh7AvWT5wnNZxTik09",
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testPostRefreshTokenWithMissingClientId()
    {
        $accessTokenEntity = json_decode($this->generateAccessTokenAndClearResolvedInstances());

        $data = &$this->testData[__FUNCTION__];

        $params = [
            'client_secret'   => $this->devClient->getSecret(),
            'refresh_token'   => $accessTokenEntity ->refresh_token ,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    /**
     * We send a redirect uri from the valid list of client uris but not
     * the one used for auth code generation.
     */
    public function testPostAccessTokenValidWrongRedirectUri()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
            'redirect_uri'  => 'https://www.example.com',
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testPostAuthCodeAndGenerateAccessToken()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->createAndSetClientWithEnvironment();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'    => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertValidAccessToken($content);
    }

    public function testPostAuthCodeAndGenerateAccessTokenInvalidInput()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->createAndSetClientWithEnvironment();

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'    => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testValidateTallyAuthUser()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->application = Application\Entity::factory()->create(
            [
                'type'          =>  'tally',
            ]
        );

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testValidateTallyAuthUserForAccountingIntegration()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->application = Application\Entity::factory()->create(
            [
                'type'          =>  'tally',
            ]
        );

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testValidateTallyAuthUserForAccountingWithViewOnly()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->application = Application\Entity::factory()->create(
            [
                'type'          =>  'tally',
            ]
        );

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testValidateTallyAuthUserInvalidInput()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->application = Application\Entity::factory()->create(
            [
                'type'          =>  'public',
            ]
        );

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testTallyToken()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->application = Application\Entity::factory()->create(
            [
                'type'          =>  'tally',
            ]
        );

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
            'client_secret'     => $this->devClient->getSecret(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertArrayHasKey('access_token', $content);

        $this->assertArrayHasKey('expires_in', $content);
    }

    public function testTallyTokenForAccountingIntegration()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));

        $this->application = Application\Entity::factory()->create(
            [
                'type'          =>  'tally',
            ]
        );

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
            'client_secret'     => $this->devClient->getSecret(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $content = $this->runRequestResponseFlow($data);

        $this->assertArrayHasKey('access_token', $content);

        $this->assertArrayHasKey('expires_in', $content);
    }

    public function testTallyTokenInvalidInput()
    {
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));
        $this->application = Application\Entity::factory()->create(
            [
                'type'          =>  'tally',
            ]
        );

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev',
            ]);

        Request::clearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_id'         => $this->devClient->getId(),
            'client_secret'     => $this->devClient->getSecret(),
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->runRequestResponseFlow($data);
    }

    public function testPostAccessTokenWithInvalidGrant()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret(),
            'code'          => $authCode,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAccessTokenWithMissingCode()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => $this->devClient->getSecret()
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectSecret()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => 'wrongSecret',
            'code'          => $authCode,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAccessTokenWithIncorrectClientId()
    {
        $authCode = $this->generateAuthCodeAndClearResolvedInstances();

        $data = & $this->testData[__FUNCTION__];

        $params = [
            'client_secret' => 'wrongSecret',
            'code'          => $authCode,
        ];

        $this->addRequestParameters($data['request']['content'], $params);

        $this->startTest();
    }

    public function testPostAuthCodeWithWrongResponseType()
    {
        $application = Application\Entity::factory()->create();

        Client\Entity::factory()->create(['application_id' => $application->id, 'environment' => 'prod']);

        Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $this->startTest();
    }

    protected function generateAuthCode()
    {
        $this->application = Application\Entity::factory()->create();
        Client\Entity::factory()->create(['application_id' => $this->application->id, 'environment' => 'prod']);

        $this->devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => 'dev'
            ]);

        $data = $this->testData['testPostAuthCode'];

        $data['request']['content']['client_id'] = $this->devClient->id;

        $response = ($this->sendRequest($data['request']))->getContent();

        $content = urldecode($response);

        $pos = strpos($content, 'code=');
        $end = strpos($content, '\'" />', $pos);

        return substr($content, $pos + 5, $end - $pos - 5);
    }

    protected function generateAccessToken()
    {
        $code = $this->generateAuthCodeAndClearResolvedInstances();
        $data = $this->testData['testPostAccessToken'];
        $params = [
            'client_id' => $this->devClient->id,
            'client_secret' => $this->devClient->getSecret(),
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost',
            'code' => $code,
            'test' => 'test',
        ];
        $this->addRequestParameters($data['request']['content'], $params);
        return ($this->sendRequest($data['request']))->getContent();
    }

    public function generateAuthCodeAndClearResolvedInstances()
    {
        $authCode = $this->generateAuthCode();

        Request::clearResolvedInstances();

        return $authCode;
    }

    public function generateAccessTokenAndClearResolvedInstances()
    {
        $authCode = $this->generateAccessToken();

        Request::clearResolvedInstances();

        return $authCode;
    }
}
