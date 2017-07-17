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

        // TODO: Fetching client twice from DB, in each of the following functions; fix.
        $scopes = $this->oauthServer->validateAuthorizeRequestAndGetScopes($input);

        $appData = $this->validateAndGetApplicationDataForAuthorize($input);

        $authorizeData = [
            'application'   => $appData,
            'scopes'        => $this->parseScopesForDisplay($scopes),
            'query_params'  => $input,
            'dashboard_url' => env('APP_DASHBOARD_URL')
        ];

        return $authorizeData;
    }

    public function postAuthCode(array $input)
    {
        $data = $this->resolveTokenOnDashboard($input['token']);

        if ($data['role'] !== 'owner')
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ROLE_NOT_ALLOWED);
        }

        $data['authorize'] = $input['permission'];

        $queryParams = htmlspecialchars_decode($data['query_params']);

        parse_str($queryParams, $queryParamsArray);

        $authCode = $this->oauthServer->getAuthCode($queryParamsArray, $data);

        // TODO: Enqueue this request after checking response times
        if ($data['authorize'] === true)
        {
            $this->notifyMerchantApplicationAuthorized($queryParamsArray['client_id'], $data['user_id']);
        }

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

    protected function getDashboardService()
    {
        $dashboardMock = env('APP_DASHBOARD_MOCK', false);

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
        // 2. Also validate the client for environment

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

    protected function parseScopesForDisplay($scopes)
    {
        $scopesArray = [];

        foreach ($scopes as $scope)
        {
            $scopesArray[$scope['id']] = $scope['description'];
        }

        return $scopesArray;
    }

    protected function notifyMerchantApplicationAuthorized(string $clientId, string $userId)
    {
        $apiMock = env('APP_API_MOCK', false);

        if ($apiMock === true)
        {
            return;
        }

        $api = $this->getApiService();

        return $api->notifyMerchant($clientId, $userId);
    }

    protected function getApiService()
    {
        return new Services\Api($this->app);
    }
}
