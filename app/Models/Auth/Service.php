<?php

namespace App\Models\Auth;

use App;
use Trace;
use App\Services;
use Razorpay\OAuth;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Token\Entity as Token;

use App\Error\ErrorCode;
use App\Constants\TraceCode;
use App\Constants\RequestParams;
use App\Exception\BadRequestException;
use App\Exception\BadRequestValidationFailureException;

class Service
{
    public function __construct()
    {
        $this->oauthServer = new OAuth\OAuthServer(env('APP_ENV'));

        $this->app = App::getFacadeRoot();
    }

    /**
     * Fetch and format the data required for the authorize page UI
     *
     * @param array $input
     *
     * @return array
     * @throws BadRequestException
     * @throws OAuth\Exception\BadRequestException
     * @throws OAuth\Exception\ServerException
     */
    public function getAuthorizeViewData(array $input): array
    {
        Trace::debug(TraceCode::AUTH_AUTHORIZE_REQUEST, $input);

        (new Validator)->validateAuthorizeRequest($input);

        // TODO: Fetching client twice from DB, in each of the following functions; fix.
        $scopes = $this->oauthServer->validateAuthorizeRequestAndGetScopes($input);

        $appData = $this->validateAndGetApplicationDataForAuthorize($input);

        $authorizeData = [
            'application'   => $appData,
            'scopes'        => $this->parseScopesForDisplay($scopes),
            'dashboard_url' => env('APP_DASHBOARD_URL')
        ];

        if (empty($appData['application']['logo']) === false)
        {
            // We use betacdn for all non-prod envs
            $cdnName = env('APP_ENV') === 'prod' ? 'cdn' : 'betacdn';

            // Constructing the cdn url for logo. We save multiple sizes of logo, using medium here
            // by adding the `_medium` after the id.
            $logoUrl = 'https://' . $cdnName . '.razorpay.com' .
                preg_replace('/\.([^\.]+$)/', '_medium.$1', $appData['application']['logo']);

            $appData['application']['logo'] = $logoUrl;
        }

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

        $this->validateLocationheader($authCode);

        $clientId = $queryParamsArray[Token::CLIENT_ID];

        $merchantId = $data[Token::MERCHANT_ID];

        // TODO: Enqueue this request after checking response times
        if ($data['authorize'] === true)
        {
            $this->notifyMerchantApplicationAuthorized(
                $clientId,
                $data[Token::USER_ID],
                $merchantId);

            $this->mapMerchantToApplication($clientId, $merchantId);
        }

        return $authCode->getHeaders()['Location'][0];
    }

    public function postAuthCodeAndGenerateAccessToken(array $input)
    {
        (new Validator)->validateRequestAccessTokenMigration($input);

        $this->validatePartnerClient($input);

        list($userInput, $userData) = $this->getAuthCodeInput($input);

        $authCode = $this->oauthServer->getAuthCode($userInput, $userData);

        $this->validateLocationheader($authCode);

        $code = $this->extractAuthCode($authCode);

        //
        // Mapping of app to merchant should happen in the API batch processing
        //

        $accessTokenData = $this->getAccessTokenInput($code, $input);

        $tokenResponse = $this->generateAccessToken($accessTokenData);

        return $tokenResponse;
    }

    private function validatePartnerClient(array $input)
    {
        $client = (new Client\Repository)->findOrFail($input[RequestParams::CLIENT_ID]);

        if ($client->application->getMerchantId() !== $input[RequestParams::PARTNER_MERCHANT_ID])
        {
            throw new BadRequestValidationFailureException('Incorrect client id for partner');
        }
    }

    private function validateLocationheader($authCode)
    {
        /**
         * In case of a wrong input (eg. wrong response_type), the redirect
         * flow is not used and we just get an error in the response which
         * we extract here and throw a relevant exception
         */
        if (empty($authCode->getHeaders()['Location']) === true)
        {
            $error = $authCode->getReasonPhrase();

            throw new OAuth\Exception\BadRequestException($error, '', [], $authCode->getStatusCode());
        }
    }

    private function extractAuthCode($authCode)
    {
        $code = $authCode->getHeaders()['Location'][0];

        $parts = parse_url($code);
        parse_str($parts['query'], $query);

        return $query['code'];
    }

    private function getAuthCodeInput(array $input)
    {
        $userInput = [
            'response_type' => 'code',
            'client_id'     => $input['client_id'],
            'redirect_uri'  => $input['redirect_uri'],
            'scope'         => 'read_write',
            'state'         => 'current_state',
        ];

        $userData = [
            'role'        => 'owner',
            'user_id'     => $input['user_id'],
            'merchant_id' => $input['merchant_id'],
            'authorize'   => true,
        ];

        return [$userInput, $userData];
    }

    private function getAccessTokenInput(string $code, array $input)
    {
        $clientId = $input[Token::CLIENT_ID];

        $client = (new Client\Repository)->findOrFailPublic($clientId);

        return [
            'code'          => $code,
            'client_id'     => $clientId,
            'grant_type'    => RequestParams::AUTHORIZATION_CODE,
            'client_secret' => $client->getSecret(),
            'redirect_uri'  => $input[RequestParams::REDIRECT_URI],
        ];
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

    public function getApiService()
    {
        $apiMock = env('APP_API_MOCK', false);

        if ($apiMock === true)
        {
            return new Services\Mock\Api();
        }

        return new Services\Api();
    }

    protected function validateAndGetApplicationDataForAuthorize(array $input): array
    {
        $clientId = $input[Token::CLIENT_ID];

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

    protected function notifyMerchantApplicationAuthorized(
        string $clientId,
        string $userId,
        string $merchantId)
    {
        $apiService = $this->getApiService();

        $apiService->notifyMerchant($clientId, $userId, $merchantId);
    }

    protected function mapMerchantToApplication(string $clientId, string $merchantId)
    {
        $apiService = $this->getApiService();

        $client = (new OAuth\Client\Repository)->findOrFailPublic($clientId);

        $appId     = $client->getApplicationId();
        $partnerId = $client->getMerchantId();

        $apiService->mapMerchantToApplication($appId, $merchantId, $partnerId);
    }
}
