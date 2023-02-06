<?php

namespace Unit\Http\Middleware;

use Mockery;
use App\Http\Middleware\ErrorHandler;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Http\Request;
use Razorpay\Trace\Facades\Trace;

class ErrorHandlerTest extends UnitTestCase
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
     * ErrorHandlerHandle should pass the request as it is pre controller layer.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testErrorHandlerHandle()
    {
        $request = new Request();

        $middleware = new ErrorHandler();
        $response = $middleware->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });

        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * ErrorHandlerTerminateWithFatalError should registered in trace if the last error is fatal.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testErrorHandlerHandleWithFatalError()
    {
        $mock = Mockery::mock(ErrorHandler::class)
            ->makePartial();

        $mockError = array("type" => E_ERROR,
            "message" => "dummy fatal error",
            "file" => "testerror.php",
            "line" => 1,
            "stack" => array(array("test stack"))
        );

        $mock->shouldReceive('getLastError')
            ->withAnyArgs()
            ->andReturn($mockError)
            ->once();

        Trace::shouldReceive('critical')
            ->once();

        $request = new Request();
        $response = $mock->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Server error"}', $response->getContent());
    }
}
