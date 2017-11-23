<?php

namespace App\Constants;

use Razorpay\Trace\TraceCode as BaseTraceCode;

class TraceCode extends BaseTraceCode
{
    // ----- Debug Codes ------
    const AUTH_AUTHORIZE_REQUEST                                    = 'AUTH_AUTHORIZE_REQUEST';

    // ----- Failure Codes ----
    const AUTH_AUTHORIZE_FAILURE                                    = 'AUTH_AUTHORIZE_FAILURE';
    const AUTH_ACCESS_TOKEN_FAILURE                                 = 'AUTH_ACCESS_TOKEN_FAILURE';
    const MERCHANT_NOTIFY_FAILED                                    = 'MERCHANT_NOTIFY_FAILED';

    // ----- Generic Codes ----
    const MISC_TOSTRING_ERROR                                       = 'MISC_TOSTRING_ERROR';
    const RECOVERABLE_EXCEPTION                                     = 'RECOVERABLE_EXCEPTION';
    const ERROR_EXCEPTION                                           = 'ERROR_EXCEPTION';
    const MISC_TRACE_CODE                                           = 'MISC_TRACE_CODE';
    const API_REQUEST                                               = 'API_REQUEST';
    const AUTH_TEST_TRACE                                           = 'AUTH_TEST_TRACE';
}

