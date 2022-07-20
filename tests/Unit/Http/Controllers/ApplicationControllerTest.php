<?php

namespace App\Tests\Unit\Http\Controllers;

use App\Constants\TraceCode;
use App\Http\Controllers\ApplicationController;
use App\Tests\Unit\UnitTestCase as UnitTestCase;
use Illuminate\Support\Facades\Request as RequestFacade;
use Mockery;
use Mockery\MockInterface;
use Razorpay\Trace\Facades\Trace;

class ApplicationControllerTest extends UnitTestCase
{
    const MERCHANT_ID = '10000000000000';
    const APPLICATION_ID = '20000000000000';
    const WEBSITE = 'https://www.example.com';
    const LOGO = '/logo/app_logo.png';

    private $applicationServiceMock;

    public function setUp(): Void
    {
        parent::setUp();
        $this->setApplicationServiceMock(Mockery::mock('overload:Razorpay\OAuth\Application\Service'));
    }

    public function tearDown(): Void
    {
        parent::tearDown();
    }

    /**
     * @param mixed $applicationServiceMock
     */
    public function setApplicationServiceMock($applicationServiceMock)
    {
        $this->applicationServiceMock = $applicationServiceMock;
    }

    /**
     * @return MockInterface
     */
    public function getApplicationServiceMock()
    {
        return $this->applicationServiceMock;
    }

    /**
     * @Test '/'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * createApplication should return Entity by Entity ID.
     * @return void
     */
    public function testCreateApplication()
    {
        $partialResponse = [
            'merchant_id' => self::MERCHANT_ID,
            'name' => 'app',
            'website' => self::WEBSITE,
            'logo_url' => self::LOGO
        ];

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::CREATE_APPLICATION_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getApplicationServiceMock()
            ->shouldReceive('create')
            ->andReturn($partialResponse);

        $controller = new ApplicationController();
        $response = $controller->create()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getApplication should return Entity by Entity ID.
     * @return void
     */
    public function testGetApplication()
    {
        $id = self::APPLICATION_ID;
        $partialResponse = [
            'merchant_id' => self::MERCHANT_ID,
            'name' => 'app',
            'website' => self::WEBSITE,
            'logo_url' => self::LOGO
        ];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_APPLICATION_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getApplicationServiceMock()
            ->shouldReceive('fetch')
            ->withArgs([$id, Mockery::any()])
            ->andReturn($partialResponse);

        $controller = new ApplicationController();
        $response = $controller->get($id)->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getMultiple should return Entity by Entity ID.
     * @return void
     */
    public function testGetMultipleApplications()
    {
        $partialResponse = [
            [
                'merchant_id' => self::MERCHANT_ID,
                'name' => 'app1',
                'website' => self::WEBSITE,
                'logo_url' => self::LOGO
            ],
            [
                'merchant_id' => self::MERCHANT_ID,
                'name' => 'app2',
                'website' => self::WEBSITE,
                'logo_url' => self::LOGO
            ]
        ];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::GET_APPLICATIONS_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getApplicationServiceMock()
            ->shouldReceive('fetchMultiple')
            ->andReturn($partialResponse);

        $controller = new ApplicationController();
        $response = $controller->getMultiple()->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/applications/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * delete should return Entity by Entity ID.
     * @return void
     */
    public function testPutApplications()
    {
        $id = self::APPLICATION_ID;
        $partialResponse = [];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::DELETE_APPLICATION_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getApplicationServiceMock()
            ->shouldReceive('delete')
            ->withArgs([$id, Mockery::any()])
            ->andReturn($partialResponse);

        $controller = new ApplicationController();
        $response = $controller->delete($id)->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }

    /**
     * @Test '/applications/{id}'
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * update should return Entity by Entity ID.
     * @return void
     */
    public function testPatchApplications()
    {
        $id = self::APPLICATION_ID;
        $partialResponse = [
            'merchant_id' => self::MERCHANT_ID,
            'name' => 'app',
            'website' => self::WEBSITE,
            'logo_url' => self::LOGO
        ];
        Trace::shouldReceive('info')
            ->withArgs([TraceCode::UPDATE_APPLICATION_REQUEST, Mockery::any()])
            ->once();
        RequestFacade::shouldReceive('all')->andReturn([]);
        $this->getApplicationServiceMock()
            ->shouldReceive('update')
            ->withArgs([$id, Mockery::any()])
            ->andReturn($partialResponse);

        $controller = new ApplicationController();
        $response = $controller->update($id)->getContent();

        $this->assertJsonStringEqualsJsonString(json_encode($partialResponse), $response);
    }
}
