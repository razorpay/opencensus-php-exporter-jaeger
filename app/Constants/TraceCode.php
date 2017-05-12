<?php

namespace App\Constants;

use Razorpay\Trace\TraceCode as BaseTraceCode;

class TraceCode extends BaseTraceCode
{
    // ----- Debug Codes ------
    const AUTH_AUTHORIZE_AUTH_CODE_REQUEST                          = 'AUTH_AUTHORIZE_AUTH_CODE_REQUEST';

    // ----- Failure Codes ----
    const AUTH_AUTHORIZE_FAILURE                                    = 'AUTH_AUTHORIZE_FAILURE';

    // ----- Generic Codes ----
    const RECOVERABLE_EXCEPTION                                     = 'RECOVERABLE_EXCEPTION';
    const ERROR_EXCEPTION                                           = 'ERROR_EXCEPTION';
    const MISC_TRACE_CODE                                           = 'MISC_TRACE_CODE';
    const API_REQUEST                                               = 'API_REQUEST';
}

