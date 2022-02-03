<?php

namespace App\Services\Mock;

use App\Error\ErrorCode;
use App\Exception\BadRequestException;

class Dashboard
{
    protected $config;

    protected $trace;

    public function getTokenData(string $token, string $merchantId)
    {
        $response = [];

        switch ($token)
        {
            case 'success':
                $response = $this->correctResponse();
                break;

            case 'invalid':
                $response = $this->invalidTokenResponse();
                break;

            case 'incorrect_response_type':
                $response = $this->invalidResponseTypeResponse();
                break;

            case 'invalid_role':
                $response = $this->invalidRoleResponse();
                break;

            case 'multi_token_success':
                $response = $this->correctResponseForMultiToken();
                break;

            case 'multi_token_invalid':
                $response = $this->invalidTokenResponseForMultiToken();
                break;

            case 'multi_token_incorrect_response_type':
                $response = $this->invalidResponseTypeResponseForMultiToken();
                break;

            case 'multi_token_invalid_role':
                $response = $this->invalidRoleResponseForMultiToken();
                break;
        }

        if (isset($response['data']) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_CLIENT_OR_USER);
        }

        return $response['data'];
    }

    protected function correctResponse()
    {
        $data = [
            'user_id'      => '20000000000000',
            'user_email'   => 'test@razorpay.com',
            'merchant_id'  => '10000000000000',
            'role'         => 'owner',
            'user'         => [
                'id'             => '20000000000000',
                'name'           => 'fdfd',
                'email'          => 'fdsfsd@dfsd.dsfd',
                'contact_mobile' => '9999999999',
                'created_at'     => '1497678977',
                'updated_at'     => '1497678977',
                'merchant_id'    => '10000000000000',
                'confirmed'      => true
            ],
            'query_params' => 'client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=code&amp;scope=read_only'
        ];

        return [
            'success' => true,
            'data'    => $data
        ];
    }

    protected function invalidTokenResponse()
    {
        $errors = ['User data not found'];

        return [
            'success' => false,
            'errors'  => $errors,
        ];
    }

    protected function invalidResponseTypeResponse()
    {
        $data = [
            'user_id'      => '20000000000000',
            'user_email'   => 'test@razorpay.com',
            'merchant_id'  => '10000000000000',
            'role'         => 'owner',
            'user'         => [
                'id'             => '20000000000000',
                'name'           => 'fdfd',
                'email'          => 'fdsfsd@dfsd.dsfd',
                'contact_mobile' => '9999999999',
                'created_at'     => '1497678977',
                'updated_at'     => '1497678977',
                'merchant_id'    => '10000000000000',
                'confirmed'      => true
            ],
            'query_params' => 'client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=invalid&amp;scope=read_only'
        ];

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    protected function invalidRoleResponse()
    {
        $data = [
            'user_id'      => '20000000000000',
            'user_email'   => 'test@razorpay.com',
            'merchant_id'  => '10000000000000',
            'role'         => 'support',
            'user'         => [
                'id'             => '20000000000000',
                'name'           => 'fdfd',
                'email'          => 'fdsfsd@dfsd.dsfd',
                'contact_mobile' => '9999999999',
                'created_at'     => '1497678977',
                'updated_at'     => '1497678977',
                'merchant_id'    => '10000000000000',
                'confirmed'      => true
            ],
            'query_params' => 'client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=invalid&amp;scope=read_only'
        ];

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    protected function correctResponseForMultiToken()
    {
        $data = [
            'user_id'      => '20000000000000',
            'user_email'   => 'test@razorpay.com',
            'merchant_id'  => '10000000000000',
            'role'         => 'owner',
            'user'         => [
                'id'             => '20000000000000',
                'name'           => 'fdfd',
                'email'          => 'fdsfsd@dfsd.dsfd',
                'contact_mobile' => '9999999999',
                'created_at'     => '1497678977',
                'updated_at'     => '1497678977',
                'merchant_id'    => '10000000000000',
                'confirmed'      => true
            ],
            'query_params' => 'live_client_id=40000000000000&amp;test_client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=code&amp;scope=read_only'
        ];

        return [
            'success' => true,
            'data'    => $data
        ];
    }

    protected function invalidTokenResponseForMultiToken()
    {
        $errors = ['User data not found'];

        return [
            'success' => false,
            'errors'  => $errors,
        ];
    }

    protected function invalidResponseTypeResponseForMultiToken()
    {
        $data = [
            'user_id'      => '20000000000000',
            'user_email'   => 'test@razorpay.com',
            'merchant_id'  => '10000000000000',
            'role'         => 'owner',
            'user'         => [
                'id'             => '20000000000000',
                'name'           => 'fdfd',
                'email'          => 'fdsfsd@dfsd.dsfd',
                'contact_mobile' => '9999999999',
                'created_at'     => '1497678977',
                'updated_at'     => '1497678977',
                'merchant_id'    => '10000000000000',
                'confirmed'      => true
            ],
            'query_params' => 'live_client_id=40000000000000&amp;test_client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=invalid&amp;scope=read_only'
        ];

        return [
            'success' => true,
            'data'    => $data,
        ];
    }

    protected function invalidRoleResponseForMultiToken()
    {
        $data = [
            'user_id'      => '20000000000000',
            'user_email'   => 'test@razorpay.com',
            'merchant_id'  => '10000000000000',
            'role'         => 'support',
            'user'         => [
                'id'             => '20000000000000',
                'name'           => 'fdfd',
                'email'          => 'fdsfsd@dfsd.dsfd',
                'contact_mobile' => '9999999999',
                'created_at'     => '1497678977',
                'updated_at'     => '1497678977',
                'merchant_id'    => '10000000000000',
                'confirmed'      => true
            ],
            'query_params' => 'live_client_id=40000000000000&amp;test_client_id=30000000000000&amp;redirect_uri=http%3A%2F%2Flocalhost&amp;response_type=invalid&amp;scope=read_only'
        ];

        return [
            'success' => true,
            'data'    => $data,
        ];
    }
}
