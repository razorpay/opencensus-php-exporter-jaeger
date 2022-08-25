<?php

namespace Unit\Service;

use Mockery;
use Exception;
use App\Constants\TraceCode;
use App\Tests\Unit\UnitTestCase;
use Razorpay\Trace\Facades\Trace;
use App\Services\RazorX\RazorXClient;
use App\Services\RazorX\RazorXConstants;

class RazorXClientTest extends UnitTestCase
{
    private $requestMock;

    /**
     * @return Mockery\MockInterface
     */
    public function getRequestMock()
    {
        return $this->requestMock;
    }

    /**
     * @param mixed $requestMock
     */
    public function setRequestMock($requestMock)
    {
        $this->requestMock = $requestMock;
    }

    public function setUp(): void
    {
        parent::setUp();
        putenv('MOCK_ENABLE_RAZORX=true');
        $this->setRequestMock(Mockery::mock('overload:App\Request\Requests'));

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
        $this->getRequestMock()
             ->shouldReceive('post')
             ->once();
        $razorXClient = new RazorXClient();
        $isEnabled    = $razorXClient->getTreatment(
            rand(1, 100),
            RazorXConstants::JWT_SIGN_ALGO,
            'live'
        );
        $this->assertEquals('on', $isEnabled);
    }

}
