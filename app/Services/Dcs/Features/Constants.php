<?php

namespace App\Services\Dcs\Features;

class Constants
{
    const ENABLE_ROUTE_PARTNERSHIPS = 'route_for_partnerships_enabled';
    const ENABLE_PARTNER_PLAT_FEE_INVOICE = 'partner_plat_fee_invoice_enabled';

    public static array $featureToDcsKeyMapping = [
        self::ENABLE_ROUTE_PARTNERSHIPS         => 'rzp/platform/partner/route/Features',
        self::ENABLE_PARTNER_PLAT_FEE_INVOICE   => 'rzp/platform/partner/route/Features'
    ];
}
