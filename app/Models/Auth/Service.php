<?php

namespace App\Models\Auth;

use App;
use Trace;
use App\Services;
use Razorpay\OAuth;
use App\Models\Auth;
use App\Constants\TraceCode;
use App\Exception\BadRequestException;
use App\Exception\ServerErrorException;

class Service
{
    public function __construct()
    {
        $this->oauthServer = new OAuth\OAuthServer();

        $this->app = App::getFacadeRoot();
    }

    public function postAuthCode(array $input)
    {
        // TODO: Validate input after improving the following few lines
        $userData['authorize'] = $input['user']['authorize'];
        $userData['email'] = $input['user']['email'];
        $userData['name'] = $input['user']['name'];
        $userData['id'] = $input['user']['id'];
        unset($input['user']);

        $authCode = $this->oauthServer->getAuthCode($input, $userData);

        return $authCode->getHeaders()['Location'][0];
    }

    public function generateAccessToken(array $input)
    {
        $data = $this->oauthServer->getAccessToken($input);

        return json_decode($data->getBody(), true);
    }

    public function getTokenData(string $token)
    {
        $dashboard = $this->getDashboardService();

        return $dashboard->getTokenData($token);
    }

    public function getDashboardService()
    {
        $dashboardMock = env('DASHBOARD_MOCK', false);

        if ($dashboardMock === true)
        {
            return new Services\Mock\Dashboard($this->app);
        }

        return new Services\Dashboard($this->app);
    }
}
