<?php

namespace App\Tests\Unit\Console\Commands;

use App\Console\Commands\PublicTokenMigrate;
use App\Constants\TraceCode;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Razorpay\OAuth\Token\Entity;
use Razorpay\Trace\Facades\Trace;


class PublicTokenMigrateTest extends UnitTestCase
{

    private $authRepositoryMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setAuthRepositoryMock(\Mockery::mock('overload:App\Models\Auth\Repository'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
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
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testPublicTokenDescription asserts if proper descriptions is set.
     * @return void
     */
    public function testPublicTokenDescription()
    {
        $command = new PublicTokenMigrate();
        $this->assertEquals('Migrate OAuth public tokens to Kong as identifier', $command->getDescription());

    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testPublicTokenHandler validates DB transaction is performed.
     * @return void
     */
    public function testPublicTokenHandler()
    {
        $token = new Entity();
        $token->fill([
            "merchant_id" => "some_merchant_id",
            "public_id" => "some_public_token",
            "ttl" => (new \DateTime('now'))->getTimestamp(),
            "mode" => 'test',
            "jti" => 'some_identifier',
            "user_id" => 'some_userId',
        ]);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::MIGRATE_PUBLIC_TOKEN_REQUEST, \Mockery::any()]);
        $this->getAuthRepositoryMock()
            ->shouldReceive('findAllAccessTokens')
            ->andReturn([$token]);

        DB::shouldReceive('transaction')
            ->once();

        $command = new PublicTokenMigrate();
        $command->handle();
    }

}
