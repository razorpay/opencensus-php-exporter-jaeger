<?php

namespace App\Tests\Unit;

use Requests_Exception;
use App\Services\Dashboard;
use App\Tests\TestCase as TestCase;

class DashboardServiceTest extends TestCase
{
    public function testDashboardService()
    {
        $this->expectException(Requests_Exception::class);

        $service = new Dashboard();

        $service->getTokenData('invalid');
    }
}
