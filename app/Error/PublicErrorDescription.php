<?php

namespace App\Error;

class PublicErrorDescription
{
    const SERVER_ERROR                                                          = 'The server encountered an error. The incident has been reported to admins';

    const BAD_REQUEST_URL_NOT_FOUND                                             = 'The requested URL was not found on the server.';
    const BAD_REQUEST_ONLY_HTTPS_ALLOWED                                        = 'Razorpay API is only available over HTTPS.';
    const BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED                                   = 'The current http method is not supported';
    const BAD_REQUEST_INVALID_ID                                                = 'The id provided does not exist';

    const BAD_REQUEST_UNAUTHORIZED                                              = 'Please provide your api key for authentication purposes.';
    const BAD_REQUEST_INVALID_CLIENT                                            = 'There was a problem with the application you are trying to connect to, please contact the application provider for support.';
}
