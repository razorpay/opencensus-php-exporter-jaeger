<?php

namespace Integration;

use App\Tests\TestCase as TestCase;
use App\Services\RazorX\RazorXClient;
use App\Services\RazorX\RazorXConstants;

class RazorXClientTest extends TestCase
{

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
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetTreatment()
    {
        $razorXClient = new RazorXClient();
        $isEnabled    = $razorXClient->getTreatment(
            rand(1, 100),
            RazorXConstants::JWT_SIGN_ALGO,
            'live'
        );
        $this->assertEquals('on', $isEnabled);
    }

}
