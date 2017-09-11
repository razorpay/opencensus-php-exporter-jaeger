<?php

namespace App\Tests\Unit;

use App\Services\Dashboard;
use App\Tests\TestCase as TestCase;
use App\Exception\BadRequestException;

class DashboardServiceTest extends TestCase
{
    public function testDashboardService()
    {
        $this->expectException(BadRequestException::class);

        $service = new Dashboard();

        $service->getTokenData('invalid');
    }
}
