<?php

namespace App\Models\Auth;

class Constant
{
    const PUBLIC_TOKEN                          = 'public_token';
    const MID                                   = 'merchant_id';
    const MODE                                  = 'mode';
    const IDENTIFIER                            = 'identifier';
    const USER_ID                               = 'user_id';
    const TTL                                   = "ttl";
    const MOBILE_APP_CLIENT_CREDENTIALS         = 'mobile_app_client_credentials';
    const MOBILE_APP_REFRESH_TOKEN              = 'mobile_app_refresh_token';
    const WHITELISTED_GRANT_TYPE_FOR_WEBHOOK    = [
        self::MOBILE_APP_CLIENT_CREDENTIALS,
        self::MOBILE_APP_REFRESH_TOKEN
    ];
}
