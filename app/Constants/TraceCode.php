<?php

namespace App\Constants;

use Razorpay\Trace\TraceCode as BaseTraceCode;

class TraceCode extends BaseTraceCode
{
    // ----- Debug Codes ------
    const AUTH_AUTHORIZE_REQUEST                                    = 'AUTH_AUTHORIZE_REQUEST';
    const POST_AUTHORIZE_REQUEST                                    = 'POST_AUTHORIZE_REQUEST';
    const GET_TOKENS_REQUEST                                        = 'GET_TOKENS_REQUEST';
    const GET_TOKEN_REQUEST                                         = 'GET_TOKEN_REQUEST';
    const REVOKE_TOKEN_REQUEST                                      = 'REVOKE_TOKEN_REQUEST';
    const CREATE_APPLICATION_REQUEST                                = 'CREATE_APPLICATION_REQUEST';
    const GET_APPLICATION_REQUEST                                   = 'GET_APPLICATION_REQUEST';
    const UPDATE_APPLICATION_REQUEST                                = 'UPDATE_APPLICATION_REQUEST';
    const DELETE_APPLICATION_REQUEST                                = 'DELETE_APPLICATION_REQUEST';
    const GET_APPLICATIONS_REQUEST                                  = 'GET_APPLICATIONS_REQUEST';
    const BANKING_ACCOUNTS_WEBHOOK_REQUEST                          = 'BANKING_ACCOUNTS_WEBHOOK_REQUEST';
    const CREATE_CLIENTS_REQUEST                                    = 'CREATE_CLIENTS_REQUEST';
    const DELETE_CLIENT_REQUEST                                     = 'DELETE_CLIENT_REQUEST';
    const MIGRATE_CLIENT_REQUEST                                    = 'MIGRATE_CLIENT_REQUEST';

    // ----- Failure Codes ----
    const AUTH_AUTHORIZE_FAILURE                                    = 'AUTH_AUTHORIZE_FAILURE';
    const AUTH_ACCESS_TOKEN_FAILURE                                 = 'AUTH_ACCESS_TOKEN_FAILURE';
    const MERCHANT_NOTIFY_FAILED                                    = 'MERCHANT_NOTIFY_FAILED';
    const ORG_DETAILS_FETCH_FAILED                                  = 'ORG_DETAILS_FETCH_FAILED';
    const MERCHANT_APP_MAPPING_FAILED                               = 'MERCHANT_APP_MAPPING_FAILED';
    const MERCHANT_APP_MAPPING_REVOKE_FAILED                        = 'MERCHANT_APP_MAPPING_REVOKE_FAILED';
    const MERCHANT_BANKING_ACCOUNTS_WEBHOOK_FAILED                  = 'MERCHANT_BANKING_ACCOUNTS_WEBHOOK_FAILED';

    // ----- Generic Codes ----
    const MISC_TOSTRING_ERROR                                       = 'MISC_TOSTRING_ERROR';
    const RECOVERABLE_EXCEPTION                                     = 'RECOVERABLE_EXCEPTION';
    const ERROR_EXCEPTION                                           = 'ERROR_EXCEPTION';
    const MISC_TRACE_CODE                                           = 'MISC_TRACE_CODE';
    const API_REQUEST                                               = 'API_REQUEST';
    const AUTH_TEST_TRACE                                           = 'AUTH_TEST_TRACE';
}

