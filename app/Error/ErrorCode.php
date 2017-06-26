<?php

namespace App\Error;

class ErrorCode
{
    /**
     * All internal un-explained and sudden errors are encapsulated
     * by the following error code.
     */
    const SERVER_ERROR                                                              = 'SERVER_ERROR';

    // Generic bad requests
    const BAD_REQUEST_UNAUTHORIZED                                                  = 'BAD_REQUEST_UNAUTHORIZED';
    const BAD_REQUEST_INVALID_ID                                                    = 'BAD_REQUEST_INVALID_ID';
    const BAD_REQUEST_ONLY_HTTPS_ALLOWED                                            = 'BAD_REQUEST_ONLY_HTTPS_ALLOWED';
    const BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED                                       = 'BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED';
    const BAD_REQUEST_ERROR                                                         = 'BAD_REQUEST_ERROR';
    const BAD_REQUEST_EXTRA_FIELDS_PROVIDED                                         = 'BAD_REQUEST_EXTRA_FIELDS_PROVIDED';
    const BAD_REQUEST_VALIDATION_FAILURE                                            = 'BAD_REQUEST_VALIDATION_FAILURE';

    const BAD_REQUEST_URL_NOT_FOUND                                                 = 'BAD_REQUEST_URL_NOT_FOUND';
    const BAD_REQUEST_INVALID_CLIENT                                                = 'BAD_REQUEST_INVALID_CLIENT';

    const SERVER_ERROR_INVALID_ARGUMENT                                             = 'SERVER_ERROR_INVALID_ARGUMENT';
    const SERVER_ERROR_DB_QUERY_FAILED                                              = 'SERVER_ERROR_DB_QUERY_FAILED';
    const SERVER_ERROR_LOGICAL_ERROR                                                = 'SERVER_ERROR_LOGICAL_ERROR';
    const SERVER_ERROR_INTEGRATION_ERROR                                            = 'SERVER_ERROR_INTEGRATION_ERROR';
    const SERVER_ERROR_RUNTIME_ERROR                                                = 'SERVER_ERROR_RUNTIME_ERROR';
    const SERVER_ERROR_TO_STRING_EXCEPTION                                          = 'SERVER_ERROR_TO_STRING_EXCEPTION';
}
