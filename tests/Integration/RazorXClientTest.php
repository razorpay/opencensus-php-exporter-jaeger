<?php

namespace Integration;
use App\Tests\TestCase as TestCase;
use App\Services\RazorX\RazorXConstants;
use ReflectionClass;
use WpOrg\Requests\Response;

class RazorXClientTest extends TestCase
{

    /**
     * @throws \ReflectionException
     */
    protected static function getMethod($obj, $name): \ReflectionMethod
    {
        $class = new ReflectionClass($obj);
        return $class->getMethod($name);
    }

    public function setUp(): void
    {
        parent::setUp();
        putenv('MOCK_RAZORX_API_CALL=true');
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * testGetTreatment gets if the razorx treatment is activated or not.
     * @return void
     */
    public function testGetTreatment()
    {
        $razorXClient = $this->app['razorx'];
        $enabled    = $razorXClient->getTreatment(
            rand(1, 100),
            RazorXConstants::JWT_SIGN_ALGO,
            'live'
        );
        $this->assertEquals('control', $enabled);
    }

    /**
     * @Test
     * testParseAndReturnResponse checks if parseAndReturnResponse
     * returns the correct parsed result when a valid JSON body is present
     * @throws \ReflectionException
     */
    public function testParseAndReturnResponse()
    {
        $response = new Response();
        $response->status_code = 200;
        $response->body = '{"value": "v1"}';

        $razorXClient = $this->app['razorx'];
        $method = self::getMethod($razorXClient, 'parseAndReturnResponse');
        $value = $method->invokeArgs($razorXClient, [$response]);
        $this->assertEquals('v1', $value);


    }

}
