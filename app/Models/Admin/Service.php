<?php

namespace App\Models\Admin;

use Razorpay\OAuth;
use Razorpay\OAuth\Base\Table;

class Service
{
    private static $entityServiceClass = [
        Table::APPLICATIONS         => OAuth\Application\Service::class,
        Table::CLIENTS              => OAuth\Client\Service::class,
        Table::REFRESH_TOKENS       => OAuth\RefreshToken\Service::class,
        Table::TOKENS               => OAuth\Token\Service::class
    ];

    public function fetchMultipleForAdmin(string $entityType, array $input): array
    {
        $class = self::$entityServiceClass[$entityType];

        if (class_exists($class) === true)
        {
            return (new $class)->fetchMultipleAdmin($input);
        }
    }

    public function fetchByIdForAdmin(string $entityType, string $entityId)
    {
        $class = self::$entityServiceClass[$entityType];

        if (class_exists($class) === true)
        {
            return (new $class)->fetchAdmin($entityId);
        }
    }
}
