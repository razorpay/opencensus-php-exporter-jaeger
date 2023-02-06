<?php

namespace App\Tests\Functional\Middleware;

use App\Http\Controllers\ApplicationController;
use App\Http\Middleware\ErrorHandler;
use App\Tests\Concerns\RequestResponseFlowTrait;
use App\Tests\TestCase as TestCase;
use Razorpay\OAuth\Application;

class ErrorHandlerTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected $application;

    /**
     * @var Application\Entity
     */
    private $applicationEntity;

    public function setup(): void
    {
        $this->testDataFilePath = __DIR__ . '/ErrorHandlerTestData.php';
        parent::setup();
        $this->setInternalAuth('rzp', env('APP_API_SECRET'));
        $this->applicationEntity = new Application\Entity();
        $this->service = new Application\Service;
    }

    public function testCreateApplicationMissingInput()
    {
        $mockError = array("type" => E_ERROR,
            "message" => "dummy fatal error",
            "file" => "testerror.php",
            "line" => 1,
            "stack" => array(array("test stack"))
        );

        $mockapp = $this->getMockBuilder(ApplicationController::class)
            ->setMethods(['create'])
            ->getMock();

        $mockapp->expects($this->exactly(1))
            ->method('create')
            ->willReturnCallback(function()
            {
                $mocknull = null;
                return $mocknull->dummy();
            });

        $mock = $this->getMockBuilder(ErrorHandler::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getLastError'])
            ->getMock();

        $mock->expects($this->exactly(1))
            ->method('getLastError')
            ->willReturn($mockError);


        $this->app->instance('App\Http\Middleware\ErrorHandler', $mock);
        $this->app->instance('App\Http\Controllers\ApplicationController', $mockapp);

        $data = $this->testData['testCreateApplication'];

        $response = $this->sendRequest($data['request']);
        $expectedString = 'Server error';

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertStringContainsString($expectedString, $response->getContent());
    }
}
