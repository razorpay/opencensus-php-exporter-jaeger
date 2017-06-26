<?php

namespace App\Models\Auth;

use App;
use Trace;
use App\Services;
use Razorpay\OAuth;

use App\Models\Auth;
use App\Error\ErrorCode;
use App\Constants\TraceCode;
use App\Exception\BadRequestException;

class Service
{
    public function __construct()
    {
        $this->oauthServer = new OAuth\OAuthServer();

        $this->app = App::getFacadeRoot();
    }

    /**
     * Fetch and format the data required for the authorize page UI
     *
     * @param array $input
     *
     * @return array
     */
    public function getAuthorizeViewData(array $input): array
    {
        Trace::debug(TraceCode::AUTH_AUTHORIZE_REQUEST, $input);

        $appData = $this->validateAndGetApplicationDataForAuthorize($input);

        //
        // TODO:
        // 1. Check scopes request in input for validity
        // 2. Format scopes for UI
        //

        $authorizeData = [
            'application'   => $appData,
            'scopes'        => [],
            'query_params'  => $input,
            'dashboard_url' => env('APP_DASHBOARD_URL')
        ];

        return $authorizeData;
    }

    public function postAuthCode(array $input)
    {
        $data = $this->resolveTokenOnDashboard($input['token']);

        $queryParams = htmlspecialchars_decode($data['query_params']);

        parse_str($queryParams, $queryParamsArray);

        $authCode = $this->oauthServer->getAuthCode($queryParamsArray, $data);

        return $authCode->getHeaders()['Location'][0];
    }

    public function generateAccessToken(array $input)
    {
        $data = $this->oauthServer->getAccessToken($input);

        return json_decode($data->getBody(), true);
    }

    protected function resolveTokenOnDashboard(string $token)
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

    protected function validateAndGetApplicationDataForAuthorize(array $input): array
    {
        $clientId = $input['client_id'];

        $client = (new OAuth\Client\Repository)->find($clientId);

        // TODO:
        // 1. Call a helper function in Client\Service instead that validates the client.type
        // 2. If a client is revoked, display a pretty error on the UI
        // 3. Here, validate the client for environment and redirect_url first

        if ($client === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_CLIENT);
        }

        $application = $client->application;

        $data = [
            'name' => $application->getName(),
            'url'  => $application->getWebsite(),
            'logo' => $application->getLogoUrl()
        ];

        return $data;
    }
}
