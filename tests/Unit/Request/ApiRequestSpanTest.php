<?php

namespace App\Request;

use App\Constants\TraceCode;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Mockery\MockInterface;
use Razorpay\Trace\Facades\Trace;
use WpOrg\Requests\Response as RequestsResponse;

class ApiRequestSpanTest extends UnitTestCase
{
    const URL = 'https://www.dummyURL.com/path?name=some_name';
    const HEADER = ['header_0' => 'some_header_0', 'header_1' => 'some_header_1'];
    private $RequestMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setRequestMock(\Mockery::mock('overload:Requests'));
    }


    /**
     * @return MockInterface
     */
    public function getRequestMock()
    {
        return $this->RequestMock;
    }

    /**
     * @param mixed $RequestMock
     */
    public function setRequestMock($RequestMock)
    {
        $this->RequestMock = $RequestMock;
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * getRequestSpanOptions should return span options based on request attributes.
     * @return void
     */
    public function testGetRequestSpanOptions()
    {
        $span = [
            'name' => 'www.dummyURL.com/path',
            'kind' => 'client',
            'sameProcessAsParentSpan' => false,
            'attributes' => [
                'span.kind' => 'client',
                'name' => 'some_name'
            ],
        ];
        $url = self::URL;
        $this->assertEquals($span, ApiRequestSpan::getRequestSpanOptions($url));
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * wrapRequestInSpan should warp request in span, make the request and handle the response.
     * @return void
     * @throws \Throwable
     */
    public function testWrapRequestInSpan()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $options = [];
        $spanOptions = [];
        $response = new RequestsResponse();

        $this->getRequestMock()
            ->shouldReceive('get')
            ->andReturn($response);

        $this->assertEquals(ApiRequestSpan::wrapRequestInSpan(
            'get',
            array($url, $headers, $options),
            $spanOptions
        ), $response);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * wrapRequestInSpan should warp request in span, make the request and handle the response with 400.
     * @return void
     * @throws \Throwable
     */
    public function testWrapRequestWith400ResponseInSpan()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $options = [];
        $spanOptions = [];

        $response = new RequestsResponse();
        $response->status_code = 400;

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::JAEGER_API_CALL_BAD_REQUEST, [
                'body' => $response->body,
            ]]);

        $this->getRequestMock()
            ->shouldReceive('get')
            ->andReturn($response);

        $this->assertEquals(ApiRequestSpan::wrapRequestInSpan(
            'get',
            array($url, $headers, $options),
            $spanOptions
        ), $response);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * wrapRequestWithExceptionInSpan should warp request in span, handle the exception while requesting.
     * @return void
     * @throws \Throwable
     */
    public function testWrapRequestWithExceptionInSpan()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $options = [];
        $spanOptions = [];

        $response = new RequestsResponse();
        $response->status_code = 400;

        $exception = new Exception('some_exception', 401);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::JAEGER_API_CALL_FAIL, [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]]);

        $this->getRequestMock()
            ->shouldReceive('get')
            ->andThrow($exception);

        try {
            ApiRequestSpan::wrapRequestInSpan('get', array($url, $headers, $options), $spanOptions);
        } catch (Exception $e) {
            $this->assertEquals($exception, $e);
        }
    }
}
