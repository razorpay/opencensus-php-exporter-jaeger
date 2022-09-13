<?php

namespace App\Tests\Unit\Console\Commands;

use App\Console\Commands\ClientMigrate;
use App\Console\Commands\PublicTokenMigrate;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Razorpay\Trace\Facades\Trace;


class ClientMigrateTest extends UnitTestCase
{

    const CLIENT_ID = 'sjdkjsjskfisj';
    const ERROR_MESSAGE = 'client_migrate_failed';

    private $coreMock;
    private $oauthClientEntityMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setOauthClientEntityMock(Mockery::mock('overload:Razorpay\OAuth\Client\Entity'));
        $this->setCoreMock(Mockery::mock('overload:Razorpay\OAuth\Client\Core'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return MockInterface
     */
    public function getCoreMock()
    {
        return $this->coreMock;
    }

    /**
     * @param mixed $coreMock
     */
    public function setCoreMock($coreMock)
    {
        $this->coreMock = $coreMock;
    }

    /**
     * @return MockInterface
     */
    public function getOauthClientEntityMock()
    {
        return $this->oauthClientEntityMock;
    }

    /**
     * @param mixed $oauthClientEntityMock
     */
    public function setOauthClientEntityMock($oauthClientEntityMock)
    {
        $this->oauthClientEntityMock = $oauthClientEntityMock;
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * validateRequest should validate request inputs based on tallyAuthorizeRequestRules defined.
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
     * validateRequest should validate request inputs based on tallyAuthorizeRequestRules defined.
     * @return void
     */
    public function testPublicTokenHandler()
    {
        $client = new \Razorpay\OAuth\Client\Entity();

        $this->getOauthClientEntityMock()
            ->shouldIgnoreMissing()
            ->shouldReceive('all')
            ->andReturn([$client]);

        $client->shouldReceive('getId')
            ->andReturn(self::CLIENT_ID);

        $client->application = new \Razorpay\OAuth\Application\Entity();

        Trace::shouldReceive('info')
            ->once();

        DB::shouldReceive('transaction')
            ->once();

        $command = new ClientMigrate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testPublicTokenHandlerWithException should throw Exception.
     * @return void
     */
    public function testPublicTokenHandlerWithException()
    {

        $client = new \Razorpay\OAuth\Client\Entity();
        $this->getOauthClientEntityMock()
            ->shouldIgnoreMissing()
            ->shouldReceive('all')
            ->andReturn([$client]);
        $client->shouldReceive('getId')
            ->andReturn('');

        $client->application = new \Razorpay\OAuth\Application\Entity();

        Trace::shouldReceive('info')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $command = new ClientMigrate();
        try {
            $command->handle();
        } catch (\Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

}
