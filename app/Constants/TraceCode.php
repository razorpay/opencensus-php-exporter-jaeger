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
    const VALIDATE_PUBLIC_TOKEN_REQUEST                             = 'VALIDATE_PUBLIC_TOKEN_REQUEST';
    const REVOKE_TOKEN_REQUEST                                      = 'REVOKE_TOKEN_REQUEST';
    const REVOKE_TOKEN_BY_PARTNER                                   = 'REVOKE_TOKEN_BY_PARTNER';
    const REVOKE_TOKEN_FOR_MOBILE_APP                               = 'REVOKE_TOKEN_FOR_MOBILE_APP';
    const CREATE_APPLICATION_REQUEST                                = 'CREATE_APPLICATION_REQUEST';
    const GET_APPLICATION_REQUEST                                   = 'GET_APPLICATION_REQUEST';
    const UPDATE_APPLICATION_REQUEST                                = 'UPDATE_APPLICATION_REQUEST';
    const DELETE_APPLICATION_REQUEST                                = 'DELETE_APPLICATION_REQUEST';
    const RESTORE_APPLICATION_REQUEST                               = 'RESTORE_APPLICATION_REQUEST';
    const GET_APPLICATIONS_REQUEST                                  = 'GET_APPLICATIONS_REQUEST';
    const BANKING_ACCOUNTS_WEBHOOK_REQUEST                          = 'BANKING_ACCOUNTS_WEBHOOK_REQUEST';
    const CREATE_CLIENTS_REQUEST                                    = 'CREATE_CLIENTS_REQUEST';
    const DELETE_CLIENT_REQUEST                                     = 'DELETE_CLIENT_REQUEST';
    const REFRESH_CLIENTS_REQUEST                                   = 'REFRESH_CLIENTS_REQUEST';
    const REFRESH_CLIENTS_REQUEST_FAILURE                           = 'REFRESH_CLIENTS_REQUEST_FAILURE';
    const MIGRATE_CLIENT_REQUEST                                    = 'MIGRATE_CLIENT_REQUEST';
    const MIGRATE_PUBLIC_TOKEN_REQUEST                              = 'MIGRATE_PUBLIC_TOKEN_REQUEST';
    const MIGRATE_PUBLIC_OAUTH_CREDS_INPUT_PARAMS                   = 'MIGRATE_PUBLIC_OAUTH_CREDS_INPUT_PARAMS';
    const MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST                        = 'MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST';
    const MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_SUCCESS                = 'MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_SUCCESS';
    const MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_FAILURE                = 'MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_FAILURE';
    const MIGRATE_PUBLIC_OAUTH_CREDS_RATE_LIMIT                     = 'MIGRATE_PUBLIC_OAUTH_CREDS_RATE_LIMIT';
    const MIGRATE_PUBLIC_OAUTH_CREDS_NUM_RECORDS                     = 'MIGRATE_PUBLIC_OAUTH_CREDS_NUM_RECORDS';
    const TALLY_AUTHORIZE_REQUEST                                   = 'TALLY_AUTHORIZE_REQUEST';
    const TALLY_TOKEN_REQUEST                                       = 'TALLY_TOKEN_REQUEST';
    const POST_AUTHORIZE_MULTI_TOKEN_REQUEST                        = 'POST_AUTHORIZE_MULTI_TOKEN_REQUEST';
    const POST_AUTHORIZE_CREATE_LIVE_TOKEN                          = 'POST_AUTHORIZE_CREATE_LIVE_TOKEN';
    const POST_AUTHORIZE_CREATE_TEST_TOKEN                          = 'POST_AUTHORIZE_CREATE_TEST_TOKEN';
    const VALIDATE_CLIENT_EDGE_SYNC                                 = 'VALIDATE_CLIENT_EDGE_SYNC';
    const REVOKE_TOKENS_REQUEST                                     = 'REVOKE_TOKENS_REQUEST';
    const REVOKE_TOKEN_FAILED                                       = 'REVOKE_TOKEN_FAILED';
    const REVOKE_APPLICATION_ACCESS_REQUEST                         = 'REVOKE_APPLICATION_ACCESS_REQUEST';
    const MERCHANT_APP_MAPPING_REVOKE_REQUEST                       = 'MERCHANT_APP_MAPPING_REVOKE_REQUEST';
    const GET_SUBMERCHANT_APPLICATIONS_REQUEST                      = 'GET_SUBMERCHANT_APPLICATIONS_REQUEST';

    // ----- Failure Codes ----
    const AUTH_AUTHORIZE_FAILURE                                    = 'AUTH_AUTHORIZE_FAILURE';
    const AUTH_ACCESS_TOKEN_FAILURE                                 = 'AUTH_ACCESS_TOKEN_FAILURE';
    const MERCHANT_NOTIFY_FAILED                                    = 'MERCHANT_NOTIFY_FAILED';
    const ORG_DETAILS_FETCH_FAILED                                  = 'ORG_DETAILS_FETCH_FAILED';
    const USER_DETAILS_FETCH_FAILED                                 = 'USER_DETAILS_FETCH_FAILED';
    const MERCHANT_APP_MAPPING_FAILED                               = 'MERCHANT_APP_MAPPING_FAILED';
    const MERCHANT_APP_MAPPING_REVOKE_FAILED                        = 'MERCHANT_APP_MAPPING_REVOKE_FAILED';
    const MERCHANT_BANKING_ACCOUNTS_WEBHOOK_FAILED                  = 'MERCHANT_BANKING_ACCOUNTS_WEBHOOK_FAILED';
    const RAVEN_GENERATE_OTP_FAILED                                 = 'RAVEN_GENERATE_OTP_FAILED';
    const RAVEN_VERIFY_OTP_FAILED                                   = 'RAVEN_VERIFY_OTP_FAILED';
    const REQUESTS_GOT_THROTTLED                                    = 'REQUESTS_GOT_THROTTLED';

    // ----- Generic Codes ----
    const MISC_TOSTRING_ERROR                                       = 'MISC_TOSTRING_ERROR';
    const RECOVERABLE_EXCEPTION                                     = 'RECOVERABLE_EXCEPTION';
    const ERROR_EXCEPTION                                           = 'ERROR_EXCEPTION';
    const MISC_TRACE_CODE                                           = 'MISC_TRACE_CODE';
    const API_REQUEST                                               = 'API_REQUEST';
    const AUTH_TEST_TRACE                                           = 'AUTH_TEST_TRACE';

    // ----- Edge Service ----
    const CREATE_OAUTH_IDENTIFIER_IN_EDGE                           = 'CREATE_OAUTH_IDENTIFIER_IN_EDGE';
    const CREATE_CONSUMER_IN_EDGE                                   = 'CREATE_CONSUMER_IN_EDGE';
    const CREATE_OAUTH_IDENTIFIER_IN_EDGE_CASSANDRA_FAILED          = 'CREATE_OAUTH_IDENTIFIER_IN_EDGE_CASSANDRA_FAILED';
    const CREATE_OAUTH_IDENTIFIER_IN_EDGE_POSTGRES_FAILED           = 'CREATE_OAUTH_IDENTIFIER_IN_EDGE_POSTGRES_FAILED';
    const GET_OAUTH_CLIENT_FROM_EDGE                                = 'GET_OAUTH_CLIENT_FROM_EDGE';

    // ----- Signer Cache ----
    const SIGNER_CACHE_CREATE_CREDENTIALS                           = 'SIGNER_CACHE_CREATE_CREDENTIALS';
    const SIGNER_CACHE_CREATE_CREDENTIALS_FAILED                    = 'SIGNER_CACHE_CREATE_CREDENTIALS_FAILED';
    const SIGNER_CACHE_REDIS_ERROR                                  = 'SIGNER_CACHE_REDIS_ERROR';
    const SIGNER_CACHE_INVALID_TTL_ERROR                            = 'SIGNER_CACHE_INVALID_TTL_ERROR';

    // ----- Razorx -----
    const RAZORX_REQUEST_FAILED                                     = 'RAZORX_REQUEST_FAILED';
    const RAZORX_SERVICE_RETRY                                      = 'RAZORX_SERVICE_RETRY';

    const SIGN_ALGO_USED                                            = 'SIGN_ALGO_USED';

    // ----- Hypertrace -----
    const OPENCENSUS_ERROR                                          = 'OPENCENSUS_ERROR';
    const JAEGER_SPAN_EXCEPTION                                     = 'JAEGER_SPAN_EXCEPTION';
    const JAEGER_INFO                                               = 'JAEGER_INFO';
    const JAEGER_API_CALL_FAIL                                      = 'JAEGER_API_CALL_FAIL';
    const JAEGER_API_CALL_BAD_REQUEST                               = 'JAEGER_API_CALL_BAD_REQUEST';
    const JAEGER_ERROR                                              = 'JAEGER_ERROR';

    // ---- Segment Analytics ----
    const SEGMENT_EVENT_PUSH                                        = 'SEGMENT_EVENT_PUSH';
    const SEGMENT_EVENT_PUSH_SUCCESS                                = 'SEGMENT_EVENT_PUSH_SUCCESS';
    const SEGMENT_EVENT_PUSH_FAILURE                                = 'SEGMENT_EVENT_PUSH_FAILURE';

    // --- Kakfa ---
    const KAFKA_CERT_ERROR                                          = "KAFKA_CERT_ERROR";
    const KAFKA_PRODUCER_FLUSH_SUCCESS                              = "KAFKA_PRODUCER_FLUSH_SUCCESS";

    // --- Data lake ---
    const DE_EVENT_PUSH_FAILURE                                     = 'DE_EVENT_PUSH_FAILURE';

    // Used in oauth logs (oauth library)
    const INVALID_CLIENT_CREDENTIALS                                = "INVALID_CLIENT_CREDENTIALS";
    const INVALID_REFRESH_TOKEN                                     = "INVALID_REFRESH_TOKEN";
    const ENCRYPTION_FAILED                                         = "ENCRYPTION_FAILED";
    const DECRYPTION_FAILED_V2                                      = "DECRYPTION_FAILED_V2";
    const DECRYPTION_FAILED_V1                                      = "DECRYPTION_FAILED_V1";

    const MISSING_CLIENT_CREDENTIALS                                = "MISSING_CLIENT_CREDENTIALS";
    // Adding this for outbox log (outbox-php pkg)
    const OUTBOX_JOB_CREATED                                        = 'OUTBOX_JOB_CREATED';

    // logged when client data doesnt have a valid application attached to it
    const OUTBOX_INVALID_CLIENT                                     = 'OUTBOX_INVALID_CLIENT';

    // logged when fatal errors are thrown
    const PHP_FATAL_ERROR                                           =  'PHP_FATAL_ERROR';

    const SPLITZ_REQUEST                                            = 'SPLITZ_REQUEST';
    const SPLITZ_REQUEST_RESPONSE                                   = 'SPLITZ_REQUEST_RESPONSE';
    const SPLITZ_REQUEST_FAILED                                     = 'SPLITZ_REQUEST_FAILED';
    const SPLITZ_INTEGRATION_ERROR                                  = 'SPLITZ_INTEGRATION_ERROR';
}

