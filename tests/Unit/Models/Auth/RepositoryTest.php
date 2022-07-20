<?php

namespace Unit\Models\Auth;

use App\Models\Auth\Repository;
use App\Tests\Unit\UnitTestCase;

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
     * @doesNotPerformAssertions
     */
    public function testGetEntityClass()
    {
        $repository = new Repository();
        $this->assertEquals('Razorpay\\OAuth\\Token\\Entity', $repository->getEntityClass());
    }

    /**
     * @Test
     * persistNewAccessToken should persist access token in Edge from Auth Service.
     * @return void
     * @doesNotPerformAssertions
     */
    /*    public function testPersistNewAccessTokenThroughException()
        {
            $edgeMock = Mockery::mock('overload:App\Services\EdgeService');
            $outboxMock = Mockery::mock('overload:Razorpay\Outbox\Job\Core');

            $application = new ApplicationEntity();
            $devClient = new ClientEntity();
            $devClient->fill([
                'id' => '30000000000000',
                'application_id' => $application->id,
                'redirect_url' => ['https://www.example.com'],
                'environment' => 'dev'
            ]);
            $token = new Entity();
            $token->setClient($devClient);

            $token->forceFill([
                Entity::MERCHANT_ID => 'absdshthyegd12',
                Entity::MODE => 'test',
                Entity::EXPIRES_AT => 1629168660,
                Entity::PUBLIC_TOKEN => 'absfehftshst12',
                Entity::USER_ID => 'absfehftshst78'
            ]);

            $ex = new \Exception('');
            $edgeMock->shouldReceive('postPublicIdToEdge')
                ->once()
                ->andThrow(ex);

            $outboxMock->shouldReceive('sendWithDelay')
                ->once();

            DB::shouldReceive('transaction')
                ->once();
            Trace::shouldReceive('traceException')
                ->withArgs([TraceCode::CREATE_APPLICATION_REQUEST, Mockery::any()])
                ->once();

            $repository = new Repository();
            $repository->persistNewAccessToken($token);
        }*/
}
