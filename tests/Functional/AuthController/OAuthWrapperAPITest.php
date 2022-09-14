<?php


namespace App\Tests\Functional\AuthController;

use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;
use App\Tests\Concerns\RequestResponseFlowTrait;
use App\Tests\TestCase as TestCase;
use League\OAuth2\Server\CryptTrait;

class OAuthWrapperAPITest extends TestCase
{
    use RequestResponseFlowTrait;
    use CryptTrait;

    protected $dashboardMock;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/OAuthWrapperAPITestData.php';

        parent::setup();
    }

    public function testGetAuthorizeMultiTokenUrlWithInvalidClientId()
    {
        $data = $this->testData[$this->getName()];

        $response = $this->sendRequest($data['request']);

        $expectedString = 'Client authentication failed';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testGetAuthorizeMultiTokenUrlWithInvalidTestClientId()
    {
        $application = Application\Entity::factory()->create();

        $prodClient = Client\Entity::factory()->create([
            'id'             => '40000000000000',
            'application_id' => $application->getId(),
            'redirect_url'   => ['https://www.example.com', 'http://localhost'],
            'environment'    => 'prod'
        ]);

        $data = [
            'method' => 'get',
            'url'    => $this->getAuthorizeMultiTokenUrl($prodClient->getId(), '30000000000000')
        ];

        $response = $this->sendRequest($data);

        $expectedString = 'Client authentication failed';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testGetAuthorizeMultiTokenUrl()
    {
        list($application, $devClient, $prodClient) = $this->createAndSetUpTestAndLiveClient();

        $data = [
            'method' => 'get',
            'url'    => $this->getAuthorizeMultiTokenUrl($prodClient->getId(), $devClient->getId())
        ];

        $response = $this->sendRequest($data);

        $expectedString = 'Allow <span class="emphasis">' .
            $application->getName() .
            '</span> to access your <span class="emphasis merchant-name"></span> account on Razorpay?';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testGetAuthorizeMultiTokenUrlWithNoStateParam()
    {
        list($application, $devClient, $prodClient) = $this->createAndSetUpTestAndLiveClient();

        $data = [
            'method' => 'get',
            'url'    => '/authorize-multi-token?response_type=code' .
                '&live_client_id=' . $devClient->getId() .
                '&test_client_id=' . $prodClient->getId() .
                '&redirect_uri=https://www.example.com' .
                '&scope=read_only'
        ];

        $response = $this->sendRequest($data);

        $expectedString = '<p><strong>Validation failed. The state field is required.</strong></p>';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }

    public function testPostAuthCodeMultiToken()
    {
        $data = & $this->testData[__FUNCTION__];

        $this->createAndSetUpTestAndLiveClient();

        $response = $this->sendRequest($data['request']);

        $content = urldecode($response->getContent());

        $parts = parse_url($response->getTargetUrl());

        parse_str($parts['query'], $query);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('http://localhost?live_code', $content);
        $this->assertArrayHasKey('live_code', $query);
        $this->assertArrayHasKey('test_code', $query);
    }

    public function testPostAuthCodeMultiTokenWithInvalidToken()
    {
        $this->createAndSetUpTestAndLiveClient();

        $this->startTest();
    }

    public function testPostAuthCodeMultiTokenWithInvalidRole()
    {
        $this->createAndSetUpTestAndLiveClient();

        $this->startTest();
    }

    public function testPostAuthCodeMultiTokenWithReject()
    {
        $this->createAndSetUpTestAndLiveClient();

        $response = $this->sendRequest($this->testData[__FUNCTION__]['request']);

        $content = urldecode($response->getContent());

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('error=access_denied', $content);
    }

    public function testPostAuthCodeMultiTokenWithWrongResponseType()
    {
        $this->createAndSetUpTestAndLiveClient();

        $this->startTest();
    }

    private function createAndSetUpTestAndLiveClient()
    {
        $application = Application\Entity::factory()->create();

        $prodClient = Client\Entity::factory()->create([
            'id'             => '40000000000000',
            'application_id' => $application->getId(),
            'redirect_url'   => ['https://www.example.com', 'http://localhost'],
            'environment'    => 'prod'
        ]);

        $devClient = Client\Entity::factory()->create(
            [
                'id'             => '30000000000000',
                'application_id' => $application->getId(),
                'redirect_url'   => ['https://www.example.com', 'http://localhost'],
                'environment'    => 'dev'
            ]);

        return [$application, $devClient, $prodClient];
    }

    private function getAuthorizeMultiTokenUrl(string $liveClientId, string $testClientId)
    {
        return '/authorize-multi-token?response_type=code' .
            '&live_client_id=' . $liveClientId .
            '&test_client_id=' . $testClientId .
            '&redirect_uri=https://www.example.com' .
            '&scope=read_only' .
            '&state=123';
    }
}
