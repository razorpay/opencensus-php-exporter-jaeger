<?php

use App\Tests\TestCase;

use App\Services\AccountService;
class AccountServiceTest extends TestCase {

    function testAccountServiceClient()
    {
        $acs = new AccountService();

        $country_code = $acs->getCountryCode("HFppZ1G3LJ1H9u");

        $this->assertEquals($country_code, "IN");
    }

}
