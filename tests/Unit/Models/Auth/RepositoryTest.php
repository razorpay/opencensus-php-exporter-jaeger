<?php

namespace Unit\Models\Auth;

use App\Models\Auth\Repository;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Razorpay\OAuth\Token\Entity;

class RepositoryTest extends UnitTestCase
{

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * getEntityClass should return Token Entity Class.
     * @return void
     */
    public function testGetEntityClass()
    {
        $repository = new Repository();
        $this->assertEquals('Razorpay\\OAuth\\Token\\Entity', $repository->getEntityClass());
    }

    /**
     * @Test
     * persistNewAccessToken should sync the public token with Edge and Signer cache and persist the access token entity in Auth Service DB.
     * @return void
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @throws \Throwable
     */
    public function testPersistNewAccessToken(): void
    {
        $edgeMock = Mockery::mock('overload:App\Services\EdgeService');
        $signerCacheMock = Mockery::mock('overload:App\Services\SignerCache');
        $tracerMock = Mockery::mock('overload:OpenCensus\Trace\Tracer');

        $application = new \Razorpay\OAuth\Application\Entity();
        $devClient = new \Razorpay\OAuth\Client\Entity();
        $devClient->fill([
            'id' => '30000000000000',
            'application_id' => $application->id,
            'redirect_url' => ['https://www.example.com'],
            'environment' => 'dev',
        ]);

        $devClient->generateSecret();

        $token = new Entity();
        $token->setClient($devClient);

        $token->forceFill([
            Entity::MERCHANT_ID => 'absdshthyegd12',
            Entity::MODE => 'test',
            Entity::EXPIRES_AT => 1629168660,
            Entity::PUBLIC_TOKEN => 'absfehftshst12',
            Entity::USER_ID => 'absfehftshst78'
        ]);

        DB::shouldReceive('transaction')
            ->twice();

        $edgeMock->shouldReceive('postPublicIdToEdge')
            ->once()
            ->with([
            "public_token" => $token->getPublicTokenWithPrefix(),
            "identifier" => $token->getIdentifier(),
            "mode" => $token->getMode(),
            "ttl" =>  Mockery::any(),
            "user_id" => $token->getUserId()
            ]);

        $signerCacheMock->shouldReceive('writeCredentials')
            ->once()
            ->with($token->getPublicTokenWithPrefix(), $token->getClient()->getSecret(), Mockery::any());

        $tracerMock->shouldReceive('inSpan')->once()->with(['name' => 'persistNewAccessToken.saveOrFail'], Mockery::any());

        $repository = new Repository();
        $repository->persistNewAccessToken($token);

    }
}
