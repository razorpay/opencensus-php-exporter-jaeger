<?php

namespace App\Tests\Functional\AuthController;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Application;

trait UtilityTrait
{
    public function assertValidAccessToken(array $content, bool $checkRefreshToken = true)
    {
        $this->assertArrayHasKey('access_token', $content);
        $this->assertArrayHasKey('expires_in', $content);
        $this->assertArrayHasKey('public_token', $content);

        if ($checkRefreshToken)
            $this->assertArrayHasKey('refresh_token', $content);

    }

    public function addRequestParameters(array & $content, array $parameters)
    {
        $content = array_merge($content, $parameters);
    }

    public function createAndSetClientWithEnvironment(string $env = 'dev')
    {
        $this->application = factory(Application\Entity::class)->create();

        $clientName = $env . 'Client';

        $this->{$clientName} = factory(Client\Entity::class)->create(
            [
                'id'             => '30000000000000',
                'application_id' => $this->application->id,
                'redirect_url'   => ['https://www.example.com'],
                'environment'    => $env,
            ]);
    }
}
