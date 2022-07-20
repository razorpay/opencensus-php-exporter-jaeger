<?php

namespace Unit\Request;

use App\Request\Requests;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Mockery\MockInterface;

class RequestsTest extends UnitTestCase
{
    const URL = 'https://www.dummyURL.com/path?name=some_name';
    const HEADER = ['header_0' => 'some_header_0', 'header_1' => 'some_header_1'];
    const ERROR_MESSAGE = 'Failed to get span options';
    private $apiRequestSpanMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->setApiRequestSpanMock(\Mockery::mock('overload:App\Request\ApiRequestSpan'));
    }


    /**
     * @return MockInterface
     */
    public function getApiRequestSpanMock()
    {
        return $this->apiRequestSpanMock;
    }

    /**
     * @param mixed $apiRequestSpanMock
     */
    public function setApiRequestSpanMock($apiRequestSpanMock)
    {
        $this->apiRequestSpanMock = $apiRequestSpanMock;
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the request in span and handle response.
     * @return void
     */
    public function testRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $data = [];
        $options = [];
        $type = Requests::GET;
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'request',
                array($url, $headers, $data, $type, $options),
                $spanOptions
            ]);

        Requests::request($url, $headers, $data, $type, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should throw Exception.
     * @return void
     */
    public function testRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $data = [];
        $options = [];
        $type = Requests::GET;
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::request($url, $headers, $data, $type, $options);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the get request in span and handle response.
     * @return void
     */
    public function testGetRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'get',
                array($url, $headers, $options),
                $spanOptions
            ]);

        Requests::get($url, $headers, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testGetRequestWithException should throw Exception.
     * @return void
     */
    public function testGetRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $data = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::get($url, $headers, $data);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the head request in span and handle response.
     * @return void
     */
    public function testHeadRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'head',
                array($url, $headers, $options),
                $spanOptions
            ]);

        Requests::head($url, $headers, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHeadRequestWithException should throw Exception.
     * @return void
     */
    public function testHeadRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::head($url, $headers);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the delete request in span and handle response.
     * @return void
     */
    public function testDeleteRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'delete',
                array($url, $headers, $options),
                $spanOptions
            ]);

        Requests::delete($url, $headers, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testDeleteRequestWithException should throw Exception.
     * @return void
     */
    public function testDeleteRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::delete($url, $headers);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the trace request in span and handle response.
     * @return void
     */
    public function testTraceRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'trace',
                array($url, $headers, $options),
                $spanOptions
            ]);

        Requests::trace($url, $headers, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testTraceRequestWithException should throw Exception.
     * @return void
     */
    public function testTraceRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::trace($url, $headers);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the post request in span and handle response.
     * @return void
     */
    public function testPostRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $data = [];
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'post',
                array($url, $headers, $data, $options),
                $spanOptions
            ]);

        Requests::post($url, $headers, $data, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testPostRequestWithException should throw Exception.
     * @return void
     */
    public function testPostRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::post($url, $headers);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the put request in span and handle response.
     * @return void
     */
    public function testPutRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $data = [];
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'put',
                array($url, $headers, $data, $options),
                $spanOptions
            ]);

        Requests::put($url, $headers, $data, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testPutRequestWithException should throw Exception.
     * @return void
     */
    public function testPutRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::put($url, $headers);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the options request in span and handle response.
     * @return void
     */
    public function testOptionsRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $data = [];
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'options',
                array($url, $headers, $data, $options),
                $spanOptions
            ]);

        Requests::options($url, $headers, $data, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testOptionRequestWithException should throw Exception.
     * @return void
     */
    public function testOptionRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::options($url, $headers);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * request should extract span options, warp the patch request in span and handle response.
     * @return void
     */
    public function testPatchRequest()
    {
        $url = self::URL;
        $headers = self::HEADER;
        $data = [];
        $options = [];
        $spanOptions = [];

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->withArgs([$url])
            ->shouldReceive('wrapRequestInSpan')
            ->withArgs([
                'patch',
                array($url, $headers, $data, $options),
                $spanOptions
            ]);

        Requests::patch($url, $headers, $data, $options);
    }

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testPatchRequestWithException should throw Exception.
     * @return void
     */
    public function testPatchRequestWithException()
    {
        $url = self::URL;
        $headers = self::HEADER;

        $this->getApiRequestSpanMock()
            ->shouldReceive('getRequestSpanOptions')
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        try {
            Requests::patch($url, $headers);
        } catch (Exception $ex) {
            $this->assertEquals(self::ERROR_MESSAGE, $ex->getMessage());
        }
    }
}
