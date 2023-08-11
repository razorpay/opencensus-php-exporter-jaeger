<?php

namespace App\Services\Dcs\Features;

class Constants
{
    const ENABLE_ROUTE_PARTNERSHIPS = 'route_for_partnerships_enabled';

    public static array $featureToDcsKeyMapping = [
        self::ENABLE_ROUTE_PARTNERSHIPS => 'rzp/platform/partner/route/Features'
    ];
}
