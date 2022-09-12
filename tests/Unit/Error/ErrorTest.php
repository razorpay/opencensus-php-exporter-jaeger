<?php


namespace Unit\Error;


use App\Error\Error;
use App\Error\ErrorCode;
use App\Error\PublicErrorCode;
use App\Exception\InvalidArgumentException;
use App\Tests\Unit\UnitTestCase;

class ErrorTest extends UnitTestCase
{
    /**
     * @Test
     * testHandleBadRequestErrors validates that correct HttpStatusCode and PublicErrorCode are set based on the ErrorCode provided
     * @return void
     */
    public function testHandleBadRequestErrors()
    {
        $errorCodes = array(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED => 401,
            ErrorCode::BAD_REQUEST_ONLY_HTTPS_ALLOWED => 403,
            ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED => 400
        );

        foreach ($errorCodes as $key => $value) {
            $error = new Error($key, "error description", null);
            $error->handleBadRequestErrors();
            $this->assertEquals(PublicErrorCode::BAD_REQUEST_ERROR, $error->getPublicErrorCode());
            $this->assertEquals($value, $error->getHttpStatusCode());
        }
    }

    /**
     * @Test
     * testCheckErrorCode validates that we get an exception if error code is null or invalid
     * @return void
     */
    public function testCheckErrorCode()
    {
        $errorCodes = array(
            "" => "null provided for errorcode",
            "ABC" => "ErrorCode: ABC is not defined");

        foreach ($errorCodes as $key => $value) {
            $exceptionThrown = false;
            try {
                Error::CheckErrorCode($key == "" ? null : $key);
            }
            catch (InvalidArgumentException $ex) {
                $this->assertEquals($value, $ex->getMessage());
                $exceptionThrown = true;
            }
            finally {
                $this->assertTrue($exceptionThrown);
            }
        }
    }
}
