<?php

namespace App\Error;

class PublicErrorDescription
{
    const GATEWAY_ERROR                                                         = 'There is a problem with the gateway causing the payment to fail';
    const SERVER_ERROR                                                          = 'The server encountered an error. The incident has been reported to admins';
    const GATEWAY_ERROR_REQUEST_TIMEOUT                                         = 'The gateway request to submit payment information timed out. Please submit your details again';

    const BAD_REQUEST_URL_NOT_FOUND                                             = 'The requested URL was not found on the server.';
    const BAD_REQUEST_ONLY_HTTPS_ALLOWED                                        = 'Razorpay API is only available over HTTPS.';
    const BAD_REQUEST_HTTP_METHOD_NOT_ALLOWED                                   = 'The current http method is not supported';
    const BAD_REQUEST_INVALID_ID                                                = 'The id provided does not exist';

    const BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED                           = 'Please provide your api key for authentication purposes.';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY                              = 'The api key provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET                           = 'The api secret provided is invalid';
    const BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED                          = 'Please provide api secret';
    const BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE                  = 'Please do not provide your secret on public sided requests';
    const BAD_REQUEST_UNAUTHORIZED_API_KEY_NOT_PROVIDED                         = 'Please provide your Razorpay Api Key Id';

    const BAD_REQUEST_OTP_MAXIMUM_ATTEMPTS_REACHED                              = 'OTP verification failed because attempt threshold has been reached';
    const BAD_REQUEST_MAXIMUM_SMS_LIMIT_REACHED                                 = 'SMS sending failed because threshold has been reached.';
    const BAD_REQUEST_INCORRECT_OTP                                             = 'Verification failed because of incorrect OTP.';

    const BAD_REQUEST_CONTACT_INCORRECT_FORMAT                                  = 'Contact number contains invalid characters, only digits and + symbol are allowed';
    const BAD_REQUEST_CONTACT_INVALID_COUNTRY_CODE                              = 'Contact number contains invalid country code';
    const BAD_REQUEST_CONTACT_TOO_SHORT                                         = 'Contact number should be at least 8 digits, including country code';
    const BAD_REQUEST_CONTACT_TOO_LONG                                          = 'Contact number should not be greater than 15 digits, including country code';
    const BAD_REQUEST_CONTACT_ONLY_INDIAN_ALLOWED                               = 'Contact number needs to be Indian.';

    const BAD_REQUEST_INVALID_STATUS_PROVIDED                                   = 'SMS status update failed because of invalid status.';

    const BAD_REQUEST_SMS_FAILED                                                = 'SMS sending failed.';
}
