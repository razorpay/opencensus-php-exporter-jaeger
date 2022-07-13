<?php

namespace App\Services\RazorX;

class RazorXConstants
{
    // feature names:
    const JWT_SIGN_ALGO = "jwt_sign_algo";

    // RazorpayX Request Info
    const APPLICATION_HEADER_NAME  = "X-Razorpay-Application";
    const APPLICATION_HEADER_VALUE = "auth_service";
    const RETRY_COUNT_KEY          = 'retry_count';
    const EVALUATE_URI             = 'evaluate';

    const DEFAULT_CASE = 'control';
    const RAZORX_ON    = 'on';

    // Params required for evaluator API
    const ID           = 'id';
    const FEATURE_FLAG = 'feature_flag';
    const ENVIRONMENT  = 'environment';
    const MODE         = 'mode';
}

