<?php

namespace App\Constants;

class RequestParams
{
    const STATE                     = 'state';
    const REDIRECT_URI              = 'redirect_uri';
    const CONTENT_TYPE              = 'application/json';

    const CLIENT_ID                 = 'client_id';
    const CLIENT_SECRET             = 'client_secret';
    const TOKEN                     = 'token';
    const TOKEN_TYPE_HINT           = 'token_type_hint';
    const LOGIN_ID                  = 'login_id';
    const MERCHANT_ID               = 'merchant_id';
    const FEATURE                   = 'feature';
    const PRODUCT                   = 'product';
    const USER_ID                   = 'user_id';
    const AUTHORIZATION_CODE        = 'authorization_code';
    const GRANT_TYPE                = 'grant_type';
    const PIN                       = 'pin';
    const NATIVE_AUTHORIZATION_CODE = 'native_authorization_code';
    const PARTNER_MERCHANT_ID       = 'partner_merchant_id';
    const LIVE_CLIENT_ID            = 'live_client_id';
    const TEST_CLIENT_ID            = 'test_client_id';

    // The rzpctx-dev-serve-user header is passed to upstream service for routing to
    // devserve environment if applicable.
    const DEV_SERVE_USER            = 'rzpctx-dev-serve-user';
}
