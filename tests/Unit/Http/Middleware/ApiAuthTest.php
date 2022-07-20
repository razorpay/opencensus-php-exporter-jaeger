<?php

namespace Unit\Http\Middleware;

use App\Http\Middleware\ApiAuth;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Http\Request;


class ApiAuthTest extends UnitTestCase
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
     * authApiValidation should validate Auth User and Password.
     * @return void
     */
    public function testAuthApiValidation()
    {
        $request = new Request();
        $request->headers->set('PHP_AUTH_USER', 'rzp');
        $request->headers->set('PHP_AUTH_PW', env('APP_API_SECRET'));

        $middleware = new ApiAuth();
        $response = $middleware->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });
        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * authApiForNonRZPUser should validate for non razorpay auth user.
     * @return void
     */
    public function testAuthApiForNonRZPUser()
    {
        $password = 'dummy_pass';
        putenv('AUTH_FOR_RZP1=' . $password);

        $request = new Request();
        $request->headers->set('PHP_AUTH_USER', 'rzp1');
        $request->headers->set('PHP_AUTH_PW', $password);

        $middleware = new ApiAuth();
        $response = $middleware->handle($request, function ($req) use ($request) {
            $this->assertEquals($request, $req);
        });
        $this->assertEquals(null, $response);
    }

    /**
     * @Test
     * authApiForInvalidUser should validate for not valid auth user.
     * @return void
     */
    public function testAuthApiForInvalidUser()
    {
        $request = new Request();
        $request->headers->set('PHP_AUTH_USER', 'rzp1');
        $request->headers->set('PHP_AUTH_PW', '');

        $middleware = new ApiAuth();
        $response = $middleware->handle($request, function () {
        });
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"error":"Unauthorized"}', $response->getContent());
    }
}
