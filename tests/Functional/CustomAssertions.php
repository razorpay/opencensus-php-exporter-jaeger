<?php

namespace App\Tests\Functional;

trait CustomAssertions
{
    public function assertExceptionClass($e, $class)
    {
        if (($e instanceof $class) === false)
        {
            echo PHP_EOL . 'Exception of class ' . $class . ' expected but not caught' . PHP_EOL;
            throw $e;
        }

        $this->assertInstanceOf($class, $e);
    }

    public function assertArraySelectiveEquals(array $expected, array $actual)
    {
        if ((isset($actual['entity'])) and (is_string($actual['entity'])))
        {
            $this->validateEntity($actual);
        }

        foreach ($expected as $key => $value)
        {
            if (is_array($value))
            {
                $this->assertArrayHasKey($key, $actual);

                $this->assertArraySelectiveEquals($expected[$key], $actual[$key]);
            }
            else
            {
                $this->assertArrayHasKey($key, $actual);

                $this->assertSame($value, $actual[$key], 'The key is: '.$key);
            }
        }
    }

    public function assertErrorMessageEquals(string $expected, string $actual)
    {
        $this->assertEquals($expected, $actual);
    }
}
