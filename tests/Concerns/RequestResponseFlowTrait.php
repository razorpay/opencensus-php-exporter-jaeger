<?php

namespace App\Tests\Concerns;

use Closure;
use Requests;

use Razorpay\Spine\Exception\ValidationFailureException as SpineException;

trait RequestResponseFlowTrait
{
    // use EntityFetchTrait;

    public function runRequestResponseFlow($data, Closure $closure = null)
    {
        $response = null;

        try
        {
            if ($closure !== null)
            {
                $response = $closure();
            }
            else
            {
                $response = $this->sendRequest($data['request']);
            }
        }
        catch (\Exception $e)
        {
            $this->checkException($e, $data);
            $this->processAndAssertException($e, $data['exception']);
            $response = $this->generatePublicJsonResponse($e);
        }
        finally
        {
            if ((isset($e) === false) and
                (isset($data['exception'])))
            {
                $this->fail('Exception ' . $data['exception']['class'] . ' expected. None caught');
            }
        }

        $this->processAndAssertStatusCode($data, $response);

        return $this->processAndAssertResponseData($data, $response);
    }

    protected function generatePublicJsonResponse(\Exception $ex)
    {
        if ($ex instanceOf SpineException)
        {
            $data = ['error' => ['description' => $ex->getMessage()]];

            return response()->json($data, 500);
        }

        $httpStatusCode = $ex->getHttpStatusCode();

        return response()->json($ex->toPublicArray(), $httpStatusCode);
    }

    protected function checkException($e, $data)
    {
        if (isset($data['exception']) === false)
        {
            throw $e;
        }
    }

    public function processAndAssertException($actual, $expected)
    {
        $class = (isset($expected['class'])) ? $expected['class'] : 'App\Exception\RecoverableException';
        $this->assertExceptionClass($actual, $class);
        $internalError = $actual->getMessage();
        $this->assertErrorMessageEquals($expected['message'], $internalError);
    }

    protected function processAndAssertResponseData($data, $response)
    {
        $actualContent = $this->getJsonContentFromResponse($response);
        $expectedContent = $data['response']['content'];
        $this->assertArraySelectiveEquals($expectedContent, $actualContent);
        return $actualContent;
    }

    protected function getJsonContentFromResponse($response)
    {
        $content = $response->getContent();
        $this->assertJson($content);
        $content = json_decode($content, true);

        return $content;
    }

    protected function processAndAssertStatusCode($data, $response)
    {
        $expectedHttpStatusCode = $this->getExpectedHttpStatusCode($data);
        $actualStatusCode = $response->getStatusCode();
        $this->assertEquals($expectedHttpStatusCode, $actualStatusCode, $response->getContent());
    }

    protected function getExpectedHttpStatusCode($data)
    {
        if (isset($data['response']['status_code']))
        {
            return $data['response']['status_code'];
        }
        else
            return 200;
    }

    protected function sendRequest($request)
    {
        $defaults = array(
            'method' => 'POST',
            'content' => array(),
            'server' => array(),
            'cookies' => array(),
            'files' => array());

        $request = array_merge($defaults, $request);

        $creds = $this->getCreds() ?? [];

        $request['server'] = array_merge($request['server'], $creds);

        $this->convertContentToString($request['content']);

        $response = $this->call(
            $request['method'],
            $request['url'],
            $request['content'],
            $request['cookies'],
            $request['files'],
            $request['server']);

        $this->response = $response;

        return $response;
    }

    protected function makeRequestAndGetContent($request, &$callback = null)
    {
        $response = $this->sendRequest($request, $callback);

        return $this->getJsonContentFromResponse($response, $callback);
    }

    protected function makeRequestAndCatchException(Closure $closure)
    {
        try
        {
            return $closure();
        }
        catch (\Exception $e)
        {
            ;
            // throw $e;
        }
    }

    public function getJsonContent($response)
    {
        $content = $response->getContent();
        $this->assertJson($content);
        return json_decode($content, true);
    }

    protected function replaceValuesRecursively(array & $data, array $toReplace)
    {
        foreach ($toReplace as $key => $value)
        {
            if (array_key_exists($key, $data))
            {
                if ((is_array($value)) and
                    (is_array($data[$key])))
                {
                    $this->replaceValuesRecursively($data[$key], $value);
                }
                else
                {
                    $data[$key] = $value;
                }
            }
            else
            {
                $data[$key] = $value;
            }
        }
    }

    protected function setRequestUrlAndMethod(& $request, $url, $method)
    {
        $request['url'] = $url;
        $request['method'] = $method;
    }

    protected function convertContentToString(& $content)
    {
        if (is_array($content) === false)
        {
            return;
        }
        foreach ($content as $key => $value)
        {
            if (is_array($value) === true)
            {
                $this->convertContentToString($value);
            }
            else
            {
                $content[$key] = (string) $value;
            }
        }
    }
}
