<?php

namespace Unit\Models\Auth;

use App\Constants\RequestParams;
use App\Exception\BadRequestValidationFailureException;
use App\Models\Auth\Validator;
use App\Tests\Unit\UnitTestCase;
use Razorpay\Spine\Exception\ValidationFailureException;

class ValidatorTest extends UnitTestCase
{

    const INVALID_LENGTH = 'Validation failed. The client id must be 14 characters.';
    const INVALID_GRANT_TYPE = 'Validation failed. The selected grant type is invalid.';
    const INVALID_EMAIL = 'Validation failed. The login id must be a valid email address.';
    const INVALID_URI_FORMAT = 'Validation failed. The redirect uri must be a valid URL.';

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * validateRequest should validate request inputs based on tallyAuthorizeRequestRules defined.
     * @return void
     * @doesNotPerformAssertions
     * @throws BadRequestValidationFailureException
     */
    public function testValidateRequest()
    {
        $validator = new Validator();
        $validator->validateRequest([
            RequestParams::CLIENT_ID => 'absdwehderuj12',
            RequestParams::MERCHANT_ID => 'absdwehderuj12',
            RequestParams::LOGIN_ID => 'dummy_mail@gmail.com',
        ], Validator::$tallyAuthorizeRequestRules);
    }

    /**
     * @Test
     * validateRequest should validate request inputs based on tallyAuthorizeRequestRules defined.
     * @return void
     */
    public function testValidateRequestThrowsExceptionOnInValidLengthClientId()
    {
        $validator = new Validator();
        try {
            $validator->validateRequest([
                RequestParams::CLIENT_ID => 'absdwehderuj',
                RequestParams::MERCHANT_ID => 'absdwehderuj12',
                RequestParams::LOGIN_ID => 'dummy_mail@gmail.com',
            ], Validator::$tallyAuthorizeRequestRules);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertEquals(self::INVALID_LENGTH, $e->getMessage());
        }
    }

    /**
     * @Test
     * validatorForTallyAccessTokenRequestRules should validate request inputs.
     * @return void
     * @doesNotPerformAssertions
     * @throws BadRequestValidationFailureException
     */
    public function testValidatorForTallyAccessTokenRequestRules()
    {
        $validator = new Validator();
        $validator->validateRequest([
            RequestParams::CLIENT_ID => 'absdwehderuj14',
            RequestParams::CLIENT_SECRET => 'secret',
            RequestParams::MERCHANT_ID => 'absdwehderuj12',
            RequestParams::LOGIN_ID => 'dummy_mail@gmail.com',
            RequestParams::GRANT_TYPE => 'tally_client_credentials',
            RequestParams::PIN => 'pin',
        ], Validator::$tallyAccessTokenRequestRules);
    }

    /**
     * @Test
     * testValidatorTallyAccessTokenRequestRulesThrowsGrantTypeException should validate request input Grant Type.
     * @return void
     */
    public function testValidatorTallyAccessTokenRequestRulesThrowsGrantTypeException()
    {
        $validator = new Validator();
        try {
            $validator->validateRequest([
                RequestParams::CLIENT_ID => 'absdwehderuj14',
                RequestParams::CLIENT_SECRET => 'secret',
                RequestParams::MERCHANT_ID => 'absdwehderuj12',
                RequestParams::LOGIN_ID => 'dummy_mail@gmail.com',
                RequestParams::GRANT_TYPE => 'tally_client_credentials_invalid',
                RequestParams::PIN => 'pin',
            ], Validator::$tallyAccessTokenRequestRules);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertEquals(self::INVALID_GRANT_TYPE, $e->getMessage());
        }
    }

    /**
     * @Test
     * validatorTallyAccessTokenRequestRulesThrowsException should validate request inputs.
     * @return void
     */
    public function testValidatorTallyAccessTokenRequestRulesThrowsExceptionOnInvalidEmail()
    {
        $validator = new Validator();
        try {
            $validator->validateRequest([
                RequestParams::CLIENT_ID => 'absdwehderuj14',
                RequestParams::CLIENT_SECRET => 'secret',
                RequestParams::MERCHANT_ID => 'absdwehderuj12',
                RequestParams::LOGIN_ID => 'dummy_mail.com',
                RequestParams::GRANT_TYPE => 'tally_client_credentials_invalid',
                RequestParams::PIN => 'pin',
            ], Validator::$tallyAccessTokenRequestRules);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertEquals(self::INVALID_EMAIL, $e->getMessage());
        }
    }


    /**
     * @Test
     * validateAuthorizeRequest should validate authorization request inputs.
     * @return void
     * @doesNotPerformAssertions
     */
    public function testValidateAuthorizeRequest()
    {
        $validator = new Validator();
        $validator->validateAuthorizeRequest([
            RequestParams::STATE => 'absdwehderuj14',
            RequestParams::REDIRECT_URI => 'https://www.example.com/',
        ]);
    }

    /**
     * @Test
     * validateAuthorizeRequest should validate invalid Authorization request inputs.
     * @return void
     */
    public function testValidateAuthorizeRequestThrowsExceptionOnWrongURI()
    {
        $validator = new Validator();
        try {
            $validator->validateAuthorizeRequest([
                RequestParams::STATE => 'absdwehderuj14',
                RequestParams::REDIRECT_URI => 'abc.com',
            ]);
        } catch (ValidationFailureException $e) {
            $this->assertEquals(self::INVALID_URI_FORMAT, $e->getMessage());
        }
    }

    /**
     * @Test
     * validateRequestAccessTokenMigration should validate AccessTokenMigration request inputs.
     * @return void
     * @doesNotPerformAssertions
     */
    public function testValidateRequestAccessTokenMigration()
    {
        $validator = new Validator();
        $validator->validateRequestAccessTokenMigration([
            RequestParams::CLIENT_ID => 'absdwehderuj14',
            RequestParams::MERCHANT_ID => 'absdwehderuj14',
            RequestParams::USER_ID => 'absdwehderuj14',
            RequestParams::REDIRECT_URI => 'https://www.example.com/',
            RequestParams::PARTNER_MERCHANT_ID => 'absdwehderuj14',
        ]);
    }

    /**
     * @Test
     * validateAuthorizeRequest should validate invalid AccessTokenMigration request inputs on wrong URI.
     * @return void
     */
    public function testValidateRequestAccessTokenMigrationThrowsExceptionOnWrongURI()
    {
        $validator = new Validator();
        try {
            $validator->validateRequestAccessTokenMigration([
                RequestParams::CLIENT_ID => 'absdwehderuj14',
                RequestParams::MERCHANT_ID => 'absdwehderuj14',
                RequestParams::USER_ID => 'absdwehderuj14',
                RequestParams::REDIRECT_URI => 'example.com/',
                RequestParams::PARTNER_MERCHANT_ID => 'absdwehderuj14',
            ]);
        } catch (ValidationFailureException $e) {
            $this->assertEquals(self::INVALID_URI_FORMAT, $e->getMessage());
        }
    }

    /**
     * @Test
     * ValidateAuthorizeRequestMultiToken should validate AuthorizeRequestMultiToken request inputs.
     * @return void
     * @doesNotPerformAssertions
     */
    public function testValidateAuthorizeRequestMultiToken()
    {
        $validator = new Validator();
        $validator->validateAuthorizeRequestMultiToken([
            RequestParams::LIVE_CLIENT_ID => 'absdwehderuj14',
            RequestParams::TEST_CLIENT_ID => 'absdwehderuj14',
            RequestParams::STATE => 'dummy_state',
            RequestParams::REDIRECT_URI => 'https://www.example.com/',
        ]);
    }

    /**
     * @Test
     * validateAuthorizeRequestMultiToken should validate invalid AuthorizeRequestMultiToken request inputs on wrong URI.
     * @return void
     */
    public function testValidateAuthorizeRequestMultiTokenThrowsExceptionOnWrongURI()
    {
        $validator = new Validator();
        try {
            $validator->validateAuthorizeRequestMultiToken([
                RequestParams::LIVE_CLIENT_ID => 'absdwehderuj14',
                RequestParams::TEST_CLIENT_ID => 'absdwehderuj14',
                RequestParams::STATE => 'dummy_state',
                RequestParams::REDIRECT_URI => 'example.com/',
            ]);
        } catch (ValidationFailureException $e) {
            $this->assertEquals(self::INVALID_URI_FORMAT, $e->getMessage());
        }
    }
}
