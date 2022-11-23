<?php

namespace App\Tests\Unit\Console\Commands;

use App\Console\Commands\ClientMigrate;
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
     * testClientMigrateHandler validates DB transaction is performed.
     * @return void
     */
    public function testClientMigrateHandler()
    {
        $client = new \Razorpay\OAuth\Client\Entity();
        $client->application = new \Razorpay\OAuth\Application\Entity();

        $this->getOauthClientEntityMock()
            ->shouldIgnoreMissing()
            ->shouldReceive('with->whereIn->withTrashed->get')
            ->andReturn([$client]);

        $client->shouldReceive('getId')
            ->andReturn(self::CLIENT_ID);

        $client_id = $client->getId();

        // set required env vars
        putenv("CLIENT_MIGRATE_ACTION=create");
        putenv("CLIENT_MIGRATE_IDS=$client_id");

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
     * testClientMigrateHandlerWithException should throw Exception when client id doesn't exists.
     * @return void
     */
    public function testClientMigrateHandlerWithException()
    {

        $client = new \Razorpay\OAuth\Client\Entity();
        $this->getOauthClientEntityMock()
            ->shouldIgnoreMissing()
            ->shouldReceive('with->whereIn->withTrashed->get')
            ->andReturn([$client]);

        $client->shouldReceive('getId')
            ->andReturn('');

        $client->application = new \Razorpay\OAuth\Application\Entity();

        // set required env vars
        putenv("CLIENT_MIGRATE_ACTION=create");
        putenv("CLIENT_MIGRATE_IDS=testclientid");

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
