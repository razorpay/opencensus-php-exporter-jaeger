<?php

namespace Unit\Models\Admin;

use App\Constants\RequestParams;
use App\Models\Token\Validator;
use App\Tests\Unit\UnitTestCase;

class ValidatorTest extends UnitTestCase
{
    /**
     * @Test
     * ValidateRevokeByPartner should validate input by validation rule.
     * @return void
     * @doesNotPerformAssertions
     */
    public function testValidateRevokeByPartner()
    {
        $input = [
            RequestParams::CLIENT_ID => 'abcd',
            RequestParams::CLIENT_SECRET => 'abcd',
            RequestParams::TOKEN => 'abcd',
            RequestParams::TOKEN_TYPE_HINT => 'access_token',
        ];
        $validator = new Validator();
        $validator->validateInput('revoke_by_partner', $input);
    }

    /**
     * @Test
     * ValidateRevokeByPartner should throw exception on failing validation rule.
     * @return void
     * @doesNotPerformAssertions
     */
    public function testValidateRevokeByPartnerThrowException()
    {
        $input = [
            RequestParams::CLIENT_ID => 1234,
            RequestParams::CLIENT_SECRET => 'abcd',
            RequestParams::TOKEN => 'abcd',
            RequestParams::TOKEN_TYPE_HINT => 'access_token',
        ];
        try {
            $validator = new Validator();
            $validator->validateInput('revoke_by_partner', $input);
        } catch (\Exception $ex) {
            $this->assertEquals('Validation failed. The client id must be a string.', $ex->getMessage());
        }
    }
}
