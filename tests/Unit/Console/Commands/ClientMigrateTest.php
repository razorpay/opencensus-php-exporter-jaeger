<?php

namespace App\Tests\Unit\Console\Commands;

use App\Console\Commands\ClientMigrate;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Mockery;
use Mockery\MockInterface;
use Razorpay\Trace\Facades\Trace;


class ClientMigrateTest extends UnitTestCase
{

    const CLIENT_ID = 'sjdkjsjskfisj';
    const APP_TYPE = 'partner';
    const ERROR_MESSAGE = 'client_migrate_failed';
    const OUTBOX_ERR_MSG = 'outbox send has to be called in a transaction';

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
     * testClientMigrateHandlerAll validates DB transaction is performed for all.
     * @return void
     */
    public function testClientMigrateHandlerAll()
    {
        $client = new \Razorpay\OAuth\Client\Entity();
        $client->application = new \Razorpay\OAuth\Application\Entity();

        $this->getOauthClientEntityMock()
            ->shouldReceive('orderBy')
            ->once()
            ->andReturn(collect([$client]));

        $command = new ClientMigrate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testOutboxSend validates outbox send is called.
     * @return void
     */
    public function testOutboxSend()
    {
        $payload['id'] = self::CLIENT_ID;
        $payload['app_type'] = self::APP_TYPE;
        putenv("ENABLE_POSTGRES_OUTBOX=true");

        $command = new ClientMigrate();
        try {
            $command->outboxSend('create', $payload);
        } catch (\Exception $ex) {
            $this->assertEquals(self::OUTBOX_ERR_MSG, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testClientMigrateHandlerID validates DB transaction is performed for id.
     * @return void
     */
    public function testClientMigrateHandlerID()
    {
        $client = new \Razorpay\OAuth\Client\Entity();
        $client->application = new \Razorpay\OAuth\Application\Entity();
        $client_id = self::CLIENT_ID;

        // set client id
        putenv("CLIENT_MIGRATE_IDS=$client_id");

        $this->getOauthClientEntityMock()
            ->shouldIgnoreMissing()
            ->shouldReceive('with->whereIn->withTrashed->get')
            ->andReturn([$client]);

        $client->shouldReceive('getId')
            ->andReturn(self::CLIENT_ID);

        Trace::shouldReceive('info')
            ->once();

        DB::shouldReceive('transaction')
            ->once();

        $testPayload = array("id" => self::CLIENT_ID, "application_type" => self::APP_TYPE);
        $this->getCoreMock()
            ->shouldReceive('getOutboxPayload')
            ->andReturn($testPayload);

        $command = new ClientMigrate();
        $command->handle();
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testClientMigrateHandlerDelete validates DB transaction is performed for delete.
     * @return void
     */
    public function testClientMigrateHandlerDelete()
    {
        $client = new \Razorpay\OAuth\Client\Entity();
        $client->application = new \Razorpay\OAuth\Application\Entity();
        $client_id = self::CLIENT_ID;

        // set env vars
        putenv("CLIENT_MIGRATE_IDS=$client_id");
        putenv("CLIENT_MIGRATE_ACTION=delete");

        $this->getOauthClientEntityMock()
            ->shouldIgnoreMissing()
            ->shouldReceive('with->whereIn->withTrashed->get')
            ->andReturn([$client]);

        $client->shouldReceive('getId')
            ->andReturn(self::CLIENT_ID);

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
