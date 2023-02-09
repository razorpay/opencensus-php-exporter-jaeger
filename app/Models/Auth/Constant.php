<?php

namespace App\Models\Auth;

class Constant
{
    const PUBLIC_TOKEN = 'public_token';
    const MID          = 'merchant_id';
    const MODE         = 'mode';
    const IDENTIFIER   = 'identifier';
    const USER_ID      = 'user_id';
    const TTL          = "ttl";

    const OUTBOX_CHECK_TOKEN_CONSISTENCY_JOB_DELAY_MS = 600 * 1000;
    const OUTBOX_PAYLOAD_SIGNER_CACHE_CHECK_TOKEN_CONSISTENCY = 'signer_cache.check_token_consistency.v1';
}
