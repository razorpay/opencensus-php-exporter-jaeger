<?php

namespace App\Models\Auth;

use Trace;
use Requests;
use Razorpay\OAuth;
use App\Models\Auth;
use App\Constants\TraceCode;

class Service
{
    public function __construct()
    {
        $this->oauthServer = new OAuth\OAuthServer();
    }

    public function postAuthCode(array $input)
    {
        // TODO: Validate input after improving the following few lines
        $userData['authorize'] = $input['user']['authorize'];
        $userData['email'] = $input['user']['email'];
        $userData['name'] = $input['user']['name'];
        $userData['id'] = $input['user']['id'];
        unset($input['user']);

        try
        {
            return $this->oauthServer->getAuthCode($input, $userData);
        }
        catch (\Exception $ex)
        {
            Trace::error(TraceCode::AUTH_AUTHORIZE_FAILURE, [$ex->getMessage()]);
        }
    }

    public function generateAccessToken(array $input)
    {
        (new Auth\Validator)->validateInput('access_token', $input);

        try
        {
            $data = $this->oauthServer->getAccessToken($input);

            return json_decode($data->getBody(), true);
        }
        catch (\Exception $ex)
        {
            Trace::error(TraceCode::AUTH_ACCESS_TOKEN_FAILURE, [$ex->getMessage()]);
        }
    }

    public function getTokenData(string $token)
    {
        $options = ['auth' => ['rzp_api', env('APP_DASHBOARD_SECRET')]];

        $response = Requests::get(
            env('APP_DASHBOARD_URL') . 'user/' . $token . '/detail',
            [],
            $options
        );

        $body = json_decode($response->body, true);

        return $body;
    }
}
