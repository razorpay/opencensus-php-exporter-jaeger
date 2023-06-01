<?php


namespace App\Tests\Functional\AuthController;

use App\Tests\Concerns\RequestResponseFlowTrait;
use App\Tests\TestCase;

class ClientCredentials extends TestCase
{
    use RequestResponseFlowTrait;
    use UtilityTrait;

    protected $application;
    protected $devClient;
    protected $modes;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/ClientCredentialsData.php';
        parent::setup();
        $this->modes = array('test', 'live');
    }

    // testPostAccessToken verifies creation of access token on passing valid params
    public function testPostAccessToken()
    {
        $this->createAndSetClientWithEnvironment();

        foreach($this->modes as $mode)
        {
            $params = [
                'client_secret' => $this->devClient->getSecret(),
                'mode' => $mode
            ];

            $data = & $this->testData[__FUNCTION__];
            $this->addRequestParameters($data['request']['content'], $params);

            $content = $this->runRequestResponseFlow($data);
            $this->assertValidAccessToken($content, false);
        }
    }

    //testPostAccessTokenWithScope verifies creation of access token when scope is provided. If scope is not provided, 'read_write' is used as scope
    public function testPostAccessTokenWithScope()
    {
        $this->createAndSetClientWithEnvironment();
        $scopes = array('read_only', 'read_write');
        foreach($this->modes as $mode)
        {
            foreach($scopes as $scope)
            {
                $params = [
                    'client_secret' => $this->devClient->getSecret(),
                    'mode' => $mode,
                    'scope' => $scope
                ];

                $data = & $this->testData['testPostAccessToken'];
                $this->addRequestParameters($data['request']['content'], $params);

                $content = $this->runRequestResponseFlow($data);
                $this->assertValidAccessToken($content, false);
            }
        }
    }

    // testPostMultipleAccessTokens verifies if we are able to create multiple access tokens for same mode
    public function testPostMultipleAccessTokens()
    {
        $this->createAndSetClientWithEnvironment();

        foreach($this->modes as $mode)
        {
            $params = [
                'client_secret' => $this->devClient->getSecret(),
                'mode' => $mode
            ];

            $data = & $this->testData['testPostAccessToken'];
            $this->addRequestParameters($data['request']['content'], $params);

            for ($i = 0; $i < 2; $i++)
            {
                $content = $this->runRequestResponseFlow($data);
                $this->assertValidAccessToken($content, false);
            }

        }
    }

    // testPostAccessTokenWithInvalidClientID verifies that an error is returned when we provide an invalid client id
    public function testPostAccessTokenWithInvalidClientID()
    {
        $this->createAndSetClientWithEnvironment();
        foreach($this->modes as $mode)
        {
            $params = [
                'client_secret' => $this->devClient->getSecret(),
                'mode' => $mode
            ];

            $data = & $this->testData[__FUNCTION__];
            $this->addRequestParameters($data['request']['content'], $params);
            $this->runRequestResponseFlow($data);
        }
    }

    // testPostAccessTokenWithInvalidClientSecret verifies that an error is returned when we provide an invalid client secret
    public function testPostAccessTokenWithInvalidClientSecret()
    {
        $this->createAndSetClientWithEnvironment();
        foreach($this->modes as $mode)
        {
            $params = [
                'mode' => $mode
            ];

            $data = & $this->testData[__FUNCTION__];
            $this->addRequestParameters($data['request']['content'], $params);
            $this->runRequestResponseFlow($data);
        }
    }

    // testPostAccessTokenWithInvalidMode verifies that an error is returned when we provide an invalid value for mode
    public function testPostAccessTokenWithInvalidMode()
    {
        $this->createAndSetClientWithEnvironment();
        $params = [
            'client_secret' => $this->devClient->getSecret()
        ];

        $data = &$this->testData[__FUNCTION__];
        $this->addRequestParameters($data['request']['content'], $params);
        $this->runRequestResponseFlow($data);
    }

    // testPostAccessTokenWithInvalidScope verifies that an error is returned when we provide an invalid value for scope
    public function testPostAccessTokenWithInvalidScope()
    {
        $this->createAndSetClientWithEnvironment();
        foreach($this->modes as $mode)
        {
            $params = [
                'client_secret' => $this->devClient->getSecret(),
                'mode' => $mode
            ];

            $data = & $this->testData[__FUNCTION__];
            $this->addRequestParameters($data['request']['content'], $params);
            $this->runRequestResponseFlow($data);
        }
    }
}
