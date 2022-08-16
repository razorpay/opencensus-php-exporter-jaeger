<?php

namespace App\Models\Auth;

use App;
use Request;
use Trace;
use App\Services;
use Razorpay\OAuth;
use App\Constants\Mode;
use App\Error\ErrorCode;
use Razorpay\OAuth\Client;
use App\Constants\TraceCode;
use App\Constants\RequestParams;
use App\Exception\BadRequestException;
use Razorpay\OAuth\Token\Entity as Token;
use App\Services\DataLake\DEEventsKafkaProducer;
use App\Services\DataLake\Event\OnBoardingEvent;
use Razorpay\OAuth\Application\Type as ApplicationType;
use App\Exception\BadRequestValidationFailureException;
use App\Services\Segment\EventCode as SegmentEventCode;
use App\Services\DataLake\EventCode as DataLakeEventCode;

class Service
{
    const ID                = 'id';
    const ROLE              = 'role';
    const OWNER             = 'owner';
    const TALLY_AUTH_OTP    = 'tally_auth_otp';
    const OTP               = 'otp';
    const LIVE               = 'live';
    const TEST               = 'test';

    protected $raven;
    private $signAlgo;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();


        $this->signAlgo = OAuth\SignAlgoConstant::ES256;

        $this->oauthServer = new OAuth\OAuthServer(env('APP_ENV'), new Repository, $this->signAlgo);

        $this->raven = $this->app['raven'];
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

    public function validateTallyUserAndSendOtp(array $input)
    {
        Trace::info(TraceCode::TALLY_AUTHORIZE_REQUEST, [
            RequestParams::CLIENT_ID    => isset($input[RequestParams::CLIENT_ID]) ? $input[RequestParams::CLIENT_ID] : null,
            RequestParams::MERCHANT_ID  => isset($input[RequestParams::MERCHANT_ID]) ? $input[RequestParams::MERCHANT_ID] : null
        ]);

        (new Validator)->validateRequest($input, Validator::$tallyAuthorizeRequestRules);

        $this->verifyTallyClient($input[RequestParams::CLIENT_ID]);

        $userId = $this->validateMerchantUser($input[RequestParams::LOGIN_ID], $input[RequestParams::MERCHANT_ID]);

        $ravenContext = $userId . '_' . $input[RequestParams::CLIENT_ID];

        // Call raven to generate OTP
        $raven = $this->raven->generateOTP($input[RequestParams::LOGIN_ID], $ravenContext);

        if (!isset($raven[self::OTP]))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_OTP_GENERATION_FAILED);
        }

        // call api to send the otp via email
        $mailResponse = $this->getApiService()->sendOTPViaEmail($input[RequestParams::CLIENT_ID], $userId, $input[RequestParams::MERCHANT_ID], $raven[self::OTP],
            $input[RequestParams::LOGIN_ID], self::TALLY_AUTH_OTP);

        if (isset($mailResponse['success'])  !== true || $mailResponse['success'] !== true)
        {
            Trace::critical(TraceCode::TALLY_AUTHORIZE_REQUEST, $mailResponse);

            throw new BadRequestException(ErrorCode::BAD_REQUEST_OTP_GENERATION_FAILED);
        }

        return ["success" => true];
    }

    public function validateMerchantUser(string $loginId, string $merchantId)
    {
        // Get user details filter by email_id
        $user = $this->getApiService()->getUserByEmail($loginId);

        if (!isset($user[self::ID]))
        {
            throw new App\Exception\LogicException("user_id not found");
        }

        if (!isset($user['merchants']))
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_OR_USER);
        }

        $merchant = $this->filterById($user['merchants'], $merchantId);

        if ($merchant === null)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_MERCHANT_OR_USER);
        }

        if (isset($merchant[self::ROLE]) && $merchant[self::ROLE] === self::OWNER)
        {
            return $user[self::ID];
        }

        throw new BadRequestException(ErrorCode::BAD_REQUEST_ROLE_NOT_ALLOWED);
    }

    public function filterById(array $list, string $id)
    {
        foreach ($list as $item)
        {
            if (isset($item[self::ID]) && $item[self::ID] === $id)
            {
                return $item;
            }
        }
    }

    private function verifyTallyClient(string $clientId){
        try
        {
            // Get application details using client and check that type is native and not public or partner
            $client = (new Client\Repository)->findOrFailPublic($clientId);

            if ($client->application->getType() == ApplicationType::TALLY)
            {
                return;
            }

        }catch (\Throwable $e) {

            $tracePayload = [
                'class' => get_class($e),
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];

            Trace::info(TraceCode::TALLY_AUTHORIZE_REQUEST, $tracePayload);
        }

        throw new BadRequestValidationFailureException('Invalid client');
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

    public function generateTallyAccessToken(array $input)
    {
        Trace::info(TraceCode::TALLY_TOKEN_REQUEST, [
            RequestParams::CLIENT_ID   => isset($input[RequestParams::CLIENT_ID]) ? $input[RequestParams::CLIENT_ID] : null,
            RequestParams::MERCHANT_ID => isset($input[RequestParams::MERCHANT_ID]) ? $input[RequestParams::MERCHANT_ID] : null,
            RequestParams::GRANT_TYPE  => isset($input[RequestParams::GRANT_TYPE]) ? $input[RequestParams::GRANT_TYPE] : null
        ]);

        (new Validator)->validateRequest($input, Validator::$tallyAccessTokenRequestRules);

        $this->verifyTallyClient($input[RequestParams::CLIENT_ID]);

        (new Client\Repository)->getClientEntity($input[RequestParams::CLIENT_ID], $input[RequestParams::GRANT_TYPE], $input[RequestParams::CLIENT_SECRET], true);

        $userId = $this->validateMerchantUser($input[RequestParams::LOGIN_ID], $input[RequestParams::MERCHANT_ID]);

        $ravenContext = $userId . '_' . $input[RequestParams::CLIENT_ID];

        // hit raven to verify OTP with body
        $otpResponse = $this->raven->verifyOTP($input[RequestParams::LOGIN_ID], $ravenContext, $input[RequestParams::PIN]);

        if (isset($otpResponse['success']) !== true || $otpResponse['success'] !== true)
        {
            throw new BadRequestValidationFailureException(ErrorCode::BAD_REQUEST_INVALID_OTP);
        }

        $accessTokenData = [
            'client_id'     => $input[RequestParams::CLIENT_ID],
            'grant_type'    => $input[RequestParams::GRANT_TYPE],
            'client_secret' => $input[RequestParams::CLIENT_SECRET],
            'merchant_id'   => $input[RequestParams::MERCHANT_ID],
            'scope'         => 'tally_read_write',
        ];

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
        Trace::info(TraceCode::SIGN_ALGO_USED, [$this->signAlgo]);

        $data = $this->oauthServer->getAccessToken($input);

        $response = json_decode($data->getBody(), true);
        //send banking accounts via webhook. TODO: send accounts webhook only once. check for previous access token present or not.

        $token = $this->oauthServer->authenticateWithPublicToken($response[Token::PUBLIC_TOKEN]);

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

    ///**
    // * @param string $mode
    // *
    // * @return bool
    // */
    //private function isRazorxExperimentEnabled(string $mode = self::LIVE)
    //{
    //    $razorxClient = $this->app['razorx'];
    //
    //    $status = $razorxClient->getTreatment(
    //        rand(1, 100),
    //        Services\RazorX\RazorXConstants::JWT_SIGN_ALGO,
    //        $mode
    //    );
    //
    //    return (strtolower($status) === 'on');
    //}

  public function getAuthorizeMultiTokenViewData(array $input)
    {
        (new Validator)->validateAuthorizeRequestMultiToken($input);

        $this->validateLiveAndTestClient($input[RequestParams::LIVE_CLIENT_ID], $input[RequestParams::TEST_CLIENT_ID]);

        $liveParams = $this->getAuthCodeMultiTokenInput($input, Mode::LIVE);

        $this->getAuthorizeViewData($liveParams);

        $testParams = $this->getAuthCodeMultiTokenInput($input, Mode::TEST);

        return $this->getAuthorizeViewData($testParams);
    }

    public function validateLiveAndTestClient(string $liveClientId, string $testClientId)
    {
        $valid_clients = $this->getValidMultiTokenClients();

        if (in_array($liveClientId, $valid_clients) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_LIVE_CLIENT);
        }

        if (in_array($testClientId, $valid_clients) === false)
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TEST_CLIENT);
        }
    }

    private function getValidMultiTokenClients()
    {
        return explode(',', env("MULTI_TOKEN_CLIENTS"));
    }

    public function postAuthCodeMultiToken(array $input)
    {
        $eventProperties = ['merchant_id' => $input['merchant_id'], 'result' => 'failure'];

        try {
            Trace::info(TraceCode::POST_AUTHORIZE_MULTI_TOKEN_REQUEST, ['merchant_id' => $input['merchant_id']]);

            if (isset($input['merchant_id']) === false)
            {
                throw new BadRequestValidationFailureException('Invalid id passed for merchant');
            }

            $data = $this->resolveTokenOnDashboard($input['token'], $input['merchant_id']);

            if ($data['role'] !== 'owner') {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_ROLE_NOT_ALLOWED);
            }

            $data['authorize'] = $input['permission'];

            $queryParams = htmlspecialchars_decode($data['query_params']);

            parse_str($queryParams, $queryParamsArray);

            Trace::info(TraceCode::POST_AUTHORIZE_CREATE_LIVE_TOKEN, ['merchant_id' => $input['merchant_id']]);

            $liveAuthCode = $this->getAuthCodeForMode($queryParamsArray, $data, Mode::LIVE);

            Trace::info(TraceCode::POST_AUTHORIZE_CREATE_TEST_TOKEN, ['merchant_id' => $input['merchant_id']]);

            $testAuthCode = $this->getAuthCodeForMode($queryParamsArray, $data, Mode::TEST);

            $redirectURL = $this->resolveRedirectUrlFromAuthCodes($queryParamsArray[RequestParams::REDIRECT_URI], $liveAuthCode, $testAuthCode, $eventProperties);

            $this->sendEvents($eventProperties, $data['user_id']);

            return $redirectURL;
        }
        catch (\Exception $e)
        {
            $eventProperties['failure_message'] = $e->getMessage();

            $this->sendEvents($eventProperties, '');

            throw $e;
        }
    }

    private function getAuthCodeMultiTokenInput(array $queryParams, $mode)
    {
        $params = array_merge(array(), $queryParams);

        unset($params[RequestParams::LIVE_CLIENT_ID], $params[RequestParams::TEST_CLIENT_ID]);

        $clientIdParamName = $mode . '_' . RequestParams::CLIENT_ID;

        if (array_key_exists($clientIdParamName, $queryParams) === true)
        {
            $params[RequestParams::CLIENT_ID] = $queryParams[$clientIdParamName];
        }

        return $params;
    }

    private function getAuthCodeForMode(array $params, array $data, $mode)
    {
        $queryParamsArray = $this->getAuthCodeMultiTokenInput($params, $mode);

        $authCode = $this->oauthServer->getAuthCode($queryParamsArray, $data);

        $this->validateLocationheader($authCode);

        // TODO: Enqueue this request after checking response times
        if ($data['authorize'] === true)
        {
            $clientId = $queryParamsArray[Token::CLIENT_ID];

            $merchantId = $data[Token::MERCHANT_ID];

            $this->mapMerchantToApplication($clientId, $merchantId);
        }

        return $authCode;
    }

    private function resolveRedirectUrlFromAuthCodes($redirectURL, $liveAuthCode, $testAuthCode, &$eventProperties)
    {
        $liveQueryParams = $this->getQueryParams($liveAuthCode);

        if (array_key_exists('code', $liveQueryParams) === false)
        {
            $eventProperties['failure_message'] = $liveQueryParams['message'];

            return $liveAuthCode->getHeaders()['Location'][0];
        }

        $testQueryParams = $this->getQueryParams($testAuthCode);

        if (array_key_exists('code', $testQueryParams) === false)
        {
            $eventProperties['failure_message'] = $testQueryParams['message'];

            return $testAuthCode->getHeaders()['Location'][0];
        }

        $params = [
            'live_code' => $liveQueryParams['code'],
            'test_code' => $testQueryParams['code']
        ];

        if (array_key_exists('state', $liveQueryParams) === true)
        {
            $params['state'] = $liveQueryParams['state'];
        }

        $eventProperties['result'] = 'success';

        return $redirectURL . '?' . http_build_query($params);
    }

    private function getQueryParams($authCode)
    {
        $locationHeader = $authCode->getHeaders()['Location'][0];

        $parts = parse_url($locationHeader);

        parse_str($parts['query'], $query);

        return $query;
    }

    private function sendEvents(array $event, string $userId)
    {
        $this->app['segment-analytics']->pushTrackEvent(
            $userId,
            $event,
            SegmentEventCode::OAUTH_MULTI_TOKEN_AUTH_CODE_GENERATED
        );

        $this->sendOnboardingEventToDE($event);
    }

    private function sendOnboardingEventToDE(array $properties)
    {
        $event = new OnBoardingEvent(DataLakeEventCode::OAUTH_MULTI_TOKEN_AUTH_CODE_GENERATE, $properties);

        (new DEEventsKafkaProducer($event))->trackEvent();
    }
}
