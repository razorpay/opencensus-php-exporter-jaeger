<?php

namespace App\Models\Auth;

use App;
use Trace;
use App\Services;
use Razorpay\OAuth;
use Razorpay\OAuth\Client;
use Razorpay\OAuth\Token\Entity as Token;
use Razorpay\OAuth\Application\Type as ApplicationType;

use App\Error\ErrorCode;
use App\Constants\TraceCode;
use App\Constants\RequestParams;
use App\Exception\BadRequestException;
use App\Exception\BadRequestValidationFailureException;

class Service
{
    const ID           = 'id';

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
        Trace::info(TraceCode::AUTH_AUTHORIZE_REQUEST, $input);

        (new Validator)->validateAuthorizeRequest($input);

        // TODO: Fetching client twice from DB, in each of the following functions; fix.
        $scopes = $this->oauthServer->validateAuthorizeRequestAndGetScopes($input);

        $appData = $this->validateAndGetApplicationDataForAuthorize($input);

        $hostName = $this->getApiService()->getOrgHostName($appData['merchant_id']);

        $authorizeData = [
            'application'   => $appData,
            'scopes'        => $this->parseScopesForDisplay($scopes),
            'dashboard_url' => $hostName,
        ];

        if (empty($authorizeData['application']['logo']) === false)
        {
            // We use betacdn for all non-prod envs
            $cdnName = env('APP_MODE') === 'prod' ? 'cdn' : 'betacdn';

            // Constructing the cdn url for logo. We save multiple sizes of logo, using medium here
            // by adding the `_medium` after the id.
            $logoUrl = 'https://' . $cdnName . '.razorpay.com' .
                preg_replace('/\.([^\.]+$)/', '_medium.$1', $authorizeData['application']['logo']);

            $authorizeData['application']['logo'] = $logoUrl;
        }

        return $authorizeData;
    }

    public function postAuthCode(array $input)
    {
        Trace::info(TraceCode::POST_AUTHORIZE_REQUEST, ['merchant_id' => $input['merchant_id']]);

        if (isset($input['merchant_id']) === false)
        {
            throw new BadRequestValidationFailureException('Invalid id passed for merchant');
        }

        $data = $this->resolveTokenOnDashboard($input['token'], $input['merchant_id']);

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

    public function validateNativeAuthUser(array $input)
    {
        Trace::info(TraceCode::VALIDATE_NATIVE_AUTH_REQUEST, [
            RequestParams::CLIENT_ID    => $input[RequestParams::CLIENT_ID],
            RequestParams::MERCHANT_ID  => $input[RequestParams::MERCHANT_ID]
        ]);

        (new Validator)->validateNativeAuthorizeRequest($input);

        // Get application details using client and check that type is native and not public or partner
        $client = (new Client\Repository)->findOrFail($input[RequestParams::CLIENT_ID]);
        if ($client->application->getType() !== ApplicationType::NATIVE)
        {
            throw new BadRequestValidationFailureException('Incorrect Application Type');
        }

        // Get user details filter by email_id
        $user = $this->getApiService()->getUserDetails($input[RequestParams::LOGIN_ID]);

        // check merchant_id is mapped to the user also
        if($input[RequestParams::MERCHANT_ID] !== $user['merchants'][0][self::ID])
        {
            throw new BadRequestValidationFailureException('Merchant does not map with the user credentials');
        }

        // Call raven to generate OTP
        $raven = $this->getRavenService()->generateOTP($input[RequestParams::CLIENT_ID], $user[self::ID], $input[RequestParams::LOGIN_ID]);

        if($raven['otp'] === null)
        {
            throw new BadRequestValidationFailureException('OTP generation failed.');
        }

        // call api to send the otp via email
        $mailResponse = $this->getApiService()->sendOTPViaMail($input[RequestParams::CLIENT_ID], $user[self::ID], $input[RequestParams::MERCHANT_ID], $raven['otp'],
            $input[RequestParams::LOGIN_ID], 'native_auth_otp');

        if($mailResponse['success'] !== true)
        {
            throw new BadRequestValidationFailureException('OTP send via mail failed.');
        }

        return ["success" => true];
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

    public function tokenNativeAuth(array $input)
    {
        Trace::info(TraceCode::TOKEN_NATIVE_AUTH_REQUEST, [
            RequestParams::CLIENT_ID => $input[RequestParams::CLIENT_ID],
            RequestParams::MERCHANT_ID => $input[RequestParams::MERCHANT_ID]
        ]);

        (new Validator)->validateNativeRequestAccessTokenRequest($input);

        // Validate Client_id and Client_secret
        $client = (new Client\Repository)->getClientEntity($input[RequestParams::CLIENT_ID], '', $input[RequestParams::CLIENT_SECRET], true);

        // Get user details filter by email_id
        $user = $this->getApiService()->getUserDetails($input[RequestParams::LOGIN_ID]);

        // check merchant_id is mapped to the user also
        if($input[RequestParams::MERCHANT_ID] !== $user['merchants'][0][self::ID])
        {
            throw new BadRequestValidationFailureException('Merchant does not map with the user credentials');
        }

        // hit raven to verify OTP with body
        $otpResponse = $this->getRavenService()->verifyOTP($input[RequestParams::CLIENT_ID], $user[self::ID], $input[RequestParams::LOGIN_ID], $input['pin']);

        if($otpResponse['success'] !== true)
        {
            throw new BadRequestValidationFailureException('Invalid OTP');
        }

        list($userInput, $userData) = $this->getAuthCodeInput($input, $user[self::ID]);

        $userInput['scope'] = 'native_read_write';
        $userInput['response_type'] = 'native_code';
        $userInput['grant_type'] = $input['grant_type'];

        $authCode = $this->oauthServer->getAuthCode($userInput, $userData);

        $this->validateLocationheader($authCode);

        $code = $this->extractAuthCode($authCode);

        // map and notify merchant
        $this->notifyMerchantApplicationAuthorized(
            $input[RequestParams::CLIENT_ID],
            $user[self::ID],
            $input[RequestParams::MERCHANT_ID]);

        $this->mapMerchantToApplication($input[RequestParams::CLIENT_ID], $input[RequestParams::MERCHANT_ID]);

        $accessTokenData = $this->getNativeAccessTokenInput($code, $input);

        $tokenResponse = $this->generateAccessToken($accessTokenData);

        unset($tokenResponse['refresh_token']);

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

    private function getAuthCodeInput(array $input, string $userId = "")
    {
        $userInput = [
            'response_type' => 'code',
            'client_id'     => $input['client_id'],
            'redirect_uri'  => $input['redirect_uri'] ?? "",
            'scope'         => 'read_write',
            'state'         => 'current_state',
        ];

        $userData = [
            'role'        => 'owner',
            'user_id'     => $input['user_id'] ?? $userId,
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

    private function getNativeAccessTokenInput(string $code, array $input)
    {
        return [
            'code'          => $code,
            'client_id'     => $input[RequestParams::CLIENT_ID],
            'grant_type'    => RequestParams::NATIVE_AUTHORIZATION_CODE,
            'client_secret' => $input[RequestParams::CLIENT_SECRET],
            'redirect_uri'  => "https://www.test.com",
        ];
    }

    public function generateAccessToken(array $input)
    {
        $data = $this->oauthServer->getAccessToken($input);

        $response = json_decode($data->getBody(), true);

        //send banking accounts via webhook. TODO: send accounts webhook only once. check for previous access token present or not.

        $token = (new OAuth\OAuthServer)->authenticateWithPublicToken($response[Token::PUBLIC_TOKEN]);

        $this->getApiService()->triggerBankingAccountsWebhook($token[Token::MERCHANT_ID], $input['mode'] ?? 'live');

        return $response;
    }

    protected function resolveTokenOnDashboard(string $token, string $merchantId)
    {
        $dashboard = $this->getDashboardService();

        return $dashboard->getTokenData($token, $merchantId);
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

    public function getRavenService()
    {
        $ravenMock = env('APP_RAVEN_MOCK', false);

        if ($ravenMock === true)
        {
            return new Services\Mock\Raven();
        }

        return new Services\Raven();
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
            'name'        => $application->getName(),
            'url'         => $application->getWebsite(),
            'logo'        => $application->getLogoUrl(),
            'merchant_id' => $application->getMerchantId(),
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
