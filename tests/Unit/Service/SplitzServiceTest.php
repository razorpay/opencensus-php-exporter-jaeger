<?php

namespace Unit\Service;

use Mockery;
use Exception;
use App\Request\Requests;
use App\Services\SplitzService;
use App\Tests\Unit\UnitTestCase;
use WpOrg\Requests\Response as RequestsResponse;

class SplitzServiceTest extends UnitTestCase
{
    private $requestMock;

    public function setUp(): void
    {
        parent::setUp();
        config(['trace.services.splitz.url' => 'https://example.com/']);
        config(['trace.services.splitz.secret' => 'secret']);
        config(['trace.services.splitz.request_timeout' => 1]);
        $this->setRequestMock(Mockery::mock('overload:App\Request\Requests'));
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return MockInterface
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

    /**
     * @Test
     * testGetEvaluateRequestWithSuccess validates if http request is made to splitz with the correct params
     * and appropriate response is returned when http request is successful
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetEvaluateRequestWithSuccess()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 200;
        $expectedResponse->body = json_encode([
            'id' => 1,
            'variant' => [
                "id" => "HHhiZaJRJbcT9Y",
                "name" => "enabled",
                "experiment_id" => "HHhiZYiI79mJbj"
            ]]
        );

        $properties = [
            'id' => 1,
            'experiment_id' => 'HHhiZYiI79mJbj'
        ];

        $this->getRequestMock()
            ->shouldReceive('post')
            ->once()
            ->withArgs(['https://example.com/twirp/rzp.splitz.evaluate.v1.EvaluateAPI/Evaluate',
                        ['Content-Type' => 'application/json'],
                        json_encode($properties),
                        ['timeout' => "1", 'auth' => ['auth', 'secret']]])
            ->andReturn($expectedResponse);

        $response = (new SplitzService())->evaluateRequest($properties);

        $this->assertEquals($expectedResponse->status_code, $response['status_code']);
        $this->assertEquals($expectedResponse->body, json_encode($response['response']));
    }

    /**
     * @Test
     * testGetEvaluateRequestWithFailure validates if appropriate error message and status code is returned
     * when http request to splitz fails.
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetEvaluateRequestWithFailure()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 400;
        $expectedResponse->body = json_encode([
            'error' => 'BAD_REQUEST_ERROR',
            'message' => 'Invalid experiment id'
            ]
        );

        $properties = [
            'id' => 1,
            'experiment_id' => 'HHhiZYiI79mJbj'
        ];

        $this->getRequestMock()
             ->shouldReceive('post')
             ->once()
             ->withArgs(['https://example.com/twirp/rzp.splitz.evaluate.v1.EvaluateAPI/Evaluate',
                         ['Content-Type' => 'application/json'],
                         json_encode($properties),
                         ['timeout' => "1", 'auth' => ['auth', 'secret']]])
             ->andReturn($expectedResponse);

        $response = (new SplitzService())->evaluateRequest($properties);

        $this->assertEquals($expectedResponse->status_code, $response['status_code']);
        $this->assertEquals($expectedResponse->body, json_encode($response['response']));
    }

    /**
     * @Test
     * testGetEvaluateRequestWithInvalidResponse validates that an exception is not thrown when splitz response body
     * has invalid json data and empty array is returned
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGetEvaluateRequestWithInvalidResponse()
    {
        $expectedResponse = new RequestsResponse();
        $expectedResponse->status_code = 200;
        $expectedResponse->body = '{"id": 1, "variant"}';

        $properties = [
            'id' => 1,
            'experiment_id' => 'HHhiZYiI79mJbj'
        ];

        $this->getRequestMock()
             ->shouldReceive('post')
             ->once()
             ->withArgs(['https://example.com/twirp/rzp.splitz.evaluate.v1.EvaluateAPI/Evaluate',
                         ['Content-Type' => 'application/json'],
                         json_encode($properties),
                         ['timeout' => "1", 'auth' => ['auth', 'secret']]])
             ->andReturn($expectedResponse);

        $response = (new SplitzService())->evaluateRequest($properties);

        $this->assertArrayNotHasKey('status_code', $response);
        $this->assertArrayNotHasKey('response', $response);
    }

    /**
     * @Test
     * testEvaluateRequestWithSplitzDisabled validatess that http request is not made to splitz
     * if it is disabled in the config
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testEvaluateRequestWithSplitzDisabled()
    {
        config(['trace.services.splitz.enabled' => false]);
        $properties = [
            'id' => 1,
            'experiment_id' => 'HHhiZYiI79mJbj'
        ];

        $this->getRequestMock()->expects($this->never())->method('post');

        $response = (new SplitzService())->evaluateRequest($properties);

        $this->assertArrayNotHasKey('status_code', $response);
        $this->assertArrayNotHasKey('response', $response);
    }

    /**
     * @Test
     * testIsExperimentEnabledForEmptyResponse validates that response is false
     * when empty array is passed as input
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testIsExperimentEnabledForEmptyResponse()
    {
        $response = (new SplitzService())->isExperimentEnabled([]);

        $this->assertFalse($response);
    }
}
