<?php

namespace App\Models\Auth;

use App;
use Trace;
use App\Services;
use Razorpay\OAuth;
use App\Constants\Mode;
use App\Error\ErrorCode;
use Razorpay\OAuth\Client;
use Illuminate\Support\Arr;
use App\Constants\TraceCode;
use App\Constants\RequestParams;
use Illuminate\Support\Collection;
use App\Exception\BadRequestException;
use Razorpay\OAuth\Token\Entity as Token;
use Razorpay\OAuth\Scope\ScopeConstants;
use App\Services\DataLake\DEEventsKafkaProducer;
use App\Services\DataLake\Event\OnBoardingEvent;
use Razorpay\OAuth\Application\Type as ApplicationType;
use App\Exception\BadRequestValidationFailureException;
use App\Services\Segment\EventCode as SegmentEventCode;
use App\Services\DataLake\EventCode as DataLakeEventCode;

class Service
{
    const ID                          = 'id';
    const ROLE                        = 'role';
    const BANKING_ROLE                = 'banking_role';
    const VIEW_ONLY                   = "view_only";
    const BANKING_TYPE                = "banking";
    const OWNER                       = 'owner';
    const TALLY_AUTH_OTP              = 'tally_auth_otp';
    const OTP                         = 'otp';
    const LIVE                        = 'live';
    const TEST                        = 'test';
    const ACCOUNTING_INTEGRATION      = "accounting_integration";

    protected $raven;
    private $signAlgo;

    protected const SCOPE_TO_POLICY_MAP = [
        ScopeConstants::READ_ONLY     => [
            "App Policies" => "https://razorpay.com/s/terms/partners/payments-oauth/read-only"
        ],
        ScopeConstants::READ_WRITE    => [
            "App Policies" => "https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/"
        ],
        ScopeConstants::RX_READ_ONLY  => [
            "RazorpayX Policies" => "https://razorpay.com/terms/razorpayx/partnership/"
        ],
        ScopeConstants::RX_READ_WRITE => [
            "RazorpayX Policies" => "https://razorpay.com/terms/razorpayx/partnership/"
        ],
    ];

    protected const SCOPES_ALLOWED_FOR_ONBOARDING = [
        ScopeConstants::READ_ONLY,
        ScopeConstants::READ_WRITE
    ];

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
     * @throws OAuth\Exception\ServerException|App\Exception\LogicException
     */
    public function getAuthorizeViewData(array $input): array
    {
        Trace::info(TraceCode::AUTH_AUTHORIZE_REQUEST, $input);

        (new Validator)->validateAuthorizeRequest($input);

        // TODO: Fetching client twice from DB, in each of the following functions; fix.
        $scopes = $this->oauthServer->validateAuthorizeRequestAndGetScopes($input);

        $appData = $this->validateAndGetApplicationDataForAuthorize($input);

        $hostName = $this->getApiService()->getOrgHostName($appData['merchant_id']);

        $isOnboardingAllowedForScope = $this->isMerchantOnboardingEnabledForScope($scopes);

        $isOnboardingExpEnabled = $this->checkIfOnboardingExpIsEnabled($appData['merchant_id'], $isOnboardingAllowedForScope);

        $scopeIds = $scopes->pluck('id')->all();

        $authorizeData = [
            'application'               => $appData,
            'scope_names'               => $scopeIds,
            'scope_descriptions'        => $this->parseScopeDescriptionsForDisplay($scopes),
            'dashboard_url'             => $hostName,
            'scope_policies'            => $this->parseScopePolicies($scopeIds),
            'platform_fee_policy_url'   => $this->fetchCustomPolicyUrlForApplication($appData['id'], $scopeIds),
            'onboarding_url'            => $this->getOnboardingUrl($appData['id'], $isOnboardingExpEnabled),
            'isOnboardingExpEnabled'    => $isOnboardingExpEnabled
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
        if (isset($input['merchant_id']) === false or empty($input['merchant_id']))
        {
            throw new BadRequestValidationFailureException('Invalid id passed for merchant');
        }

        Trace::info(TraceCode::POST_AUTHORIZE_REQUEST, ['merchant_id' => $input['merchant_id']]);

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

            $scopes = is_string($queryParamsArray['scope']) ? [$queryParamsArray['scope']] : $queryParamsArray['scope'];

            $scopePolicies = $this->parseScopePolicies($scopes);

            $this->addCustomPolicyIfApplicable($scopePolicies, $scopes, $input);

            $this->mapMerchantToApplication($clientId, $merchantId, $scopePolicies);
        }

        return $authCode->getHeaders()['Location'][0];
    }

    public function validateTallyUserAndSendOtp(array $input)
    {
        Trace::info(TraceCode::TALLY_AUTHORIZE_REQUEST, [
            RequestParams::CLIENT_ID    => isset($input[RequestParams::CLIENT_ID]) ? $input[RequestParams::CLIENT_ID] : null,
            RequestParams::MERCHANT_ID  => isset($input[RequestParams::MERCHANT_ID]) ? $input[RequestParams::MERCHANT_ID] : null
        ]);

        $product = isset($input[RequestParams::PRODUCT]) ? $input[RequestParams::PRODUCT] : '';

        $feature = isset($input[RequestParams::FEATURE]) ? $input[RequestParams::FEATURE] : '';

        (new Validator)->validateRequest($input, Validator::$tallyAuthorizeRequestRules);

        $this->verifyTallyClient($input[RequestParams::CLIENT_ID]);

        $userId = $this->validateMerchantUserWithFeature($input[RequestParams::LOGIN_ID], $input[RequestParams::MERCHANT_ID], $product, $feature);

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

    public function validateMerchantUserWithFeature(string $loginId, string $merchantId, string $product, string $feature)
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

        // Allowing all users except view only, if the request is received from banking(RX) product and accounting_integration
        // TODO: add metric in case of failure
        if ($product === self::BANKING_TYPE && $feature === self::ACCOUNTING_INTEGRATION)
        {
            if (isset($merchant[self::BANKING_ROLE]) && $merchant[self::BANKING_ROLE] !== self::VIEW_ONLY)
            {
                return $user[self::ID];
            }

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ROLE_NOT_ALLOWED);
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

        $product = isset($input[RequestParams::PRODUCT]) ? $input[RequestParams::PRODUCT] : '';

        $feature = isset($input[RequestParams::FEATURE]) ? $input[RequestParams::FEATURE] : '';

        (new Validator)->validateRequest($input, Validator::$tallyAccessTokenRequestRules);

        $this->verifyTallyClient($input[RequestParams::CLIENT_ID]);

        (new Client\Repository)->getClientEntity($input[RequestParams::CLIENT_ID], $input[RequestParams::GRANT_TYPE], $input[RequestParams::CLIENT_SECRET], true);

        $userId = $this->validateMerchantUserWithFeature($input[RequestParams::LOGIN_ID], $input[RequestParams::MERCHANT_ID], $product, $feature);

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
            'id'          => $application->getId(),
            'name'        => $application->getName(),
            'url'         => $application->getWebsite(),
            'logo'        => $application->getLogoUrl(),
            'merchant_id' => $application->getMerchantId(),
        ];

        return $data;
    }

    /**
     * Filter out scopes.
     * Currently, only drops read-only scopes if read-write scope is present
     *
     * @param array $scopes
     *
     * @return array
     */
    protected function filterScopes(array $scopes): array
    {
        return array_filter($scopes, function ($item) use ($scopes) {
            if ($item === ScopeConstants::READ_ONLY && in_array(ScopeConstants::READ_WRITE, $scopes)) {
                return false;
            }
            if ($item === ScopeConstants::RX_READ_ONLY && in_array(ScopeConstants::RX_READ_WRITE, $scopes)) {
                return false;
            }
            return true;
        });
    }

    /**
     * Maps the scope name to corresponding TnC Policy URL
     * @param array $scopes
     *
     * @return array
     */
    protected function parseScopePolicies(array $scopes): array
    {
        $scopes = $this->filterScopes($scopes);

        $scopePoliciesArray = array_filter($scopes, function ($item) {
            return array_key_exists($item, self::SCOPE_TO_POLICY_MAP);
        });

        $scopePoliciesArray = array_map(function ($item) {
            return self::SCOPE_TO_POLICY_MAP[$item];
        }, $scopePoliciesArray);

        return array_unique(array_merge(...$scopePoliciesArray));
    }

    /**
     * Extracts the scope descriptions from the collection of Scopes
     * @param Collection $scopes
     *
     * @return array
     */
    protected function parseScopeDescriptionsForDisplay(Collection $scopes): array
    {
        $filteredScopeNames = $this->filterScopes($scopes->pluck('id')->all());

        $filteredScopeObjects = array_filter($scopes->all(), function ($item) use ($filteredScopeNames) {
            return in_array($item['id'], $filteredScopeNames);
        });

       $filteredDescriptions = array_column($filteredScopeObjects, 'description');

        return array_unique(Arr::collapse($filteredDescriptions));
    }

    protected function notifyMerchantApplicationAuthorized(
        string $clientId,
        string $userId,
        string $merchantId)
    {
        $apiService = $this->getApiService();

        $apiService->notifyMerchant($clientId, $userId, $merchantId);
    }

    /**
     * This function calls API service to map merchant to the partner application.
     * @param string $clientId
     * @param string $merchantId
     * @param array $scopePolicies - This is an array of mapping of policy name and policy Url. We are consuming this
     * data in API to generate consent docs via BVS based on scopes requested during authorize.
     *
     * @return void
     */
    protected function mapMerchantToApplication(string $clientId, string $merchantId, array $scopePolicies): void
    {
        $apiService = $this->getApiService();

        $client = (new OAuth\Client\Repository)->findOrFailPublic($clientId);

        $appId     = $client->getApplicationId();

        $partnerId = $client->getMerchantId();

        $data = [
            'env'                       => $client->getEnvironment(),
            'ip'                        => $this->app['request']->ip(),
            'scope_policies'            => $scopePolicies
        ];

        $apiService->mapMerchantToApplication($appId, $merchantId, $partnerId, $data);
    }

    /**
     * This function calls API service to fetch custom TnC url for a partner application.
     * This custom TnC url will then be rendered in authorise page so that consent of
     * the merchant can be captured after authorisation.
     * @param string $appId
     * @param array $scopes
     *
     * @return void
     * @throws \Exception
     */
    protected function fetchCustomPolicyUrlForApplication(string $appId, array $scopes)
    {
        // don't fetch custom TnC url if both read_only & read_write scopes doesn't exist
        if (in_array(ScopeConstants::READ_ONLY, $scopes) === false and
            in_array(ScopeConstants::READ_WRITE, $scopes) === false)
        {
            return null;
        }

        $startTime = microtime(true);

        $apiService = $this->getApiService();

        $response =  $apiService->fetchPartnerConfigForApplication($appId);

        $endTime = microtime(true);

        Trace::info(TraceCode::PARTNER_CONFIG_FETCH,
            [
                'response' => $response,
                'time_taken' => $endTime - $startTime
            ]
        );

        return $response['partner_metadata']['policy_url'] ?? null;
    }

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

    private function getValidMultiTokenClients()
    {
        return explode(',', env("MULTI_TOKEN_CLIENTS"));
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

            $scopes = is_string($queryParamsArray['scope']) ? [$queryParamsArray['scope']] : $queryParamsArray['scope'];

            $scopePolicies = $this->parseScopePolicies($scopes);

            $this->mapMerchantToApplication($clientId, $merchantId, $scopePolicies);
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

    private function getOnboardingUrl(string $applicationId, bool $isPhantomOnboardingEnabled) : ?string
    {
        if ($isPhantomOnboardingEnabled)
        {
            $phantomUrl = $this->app['config']['trace.app.phantom_onboarding_url'];

            return $phantomUrl . '?applicationId=' . $applicationId;
        }

        return null;
    }

    private function checkIfOnboardingExpIsEnabled(string $merchantId, bool $isOnboardingAllowed) : bool
    {
        if (!$isOnboardingAllowed)
        {
            return false;
        }

        $properties = [
            'id'            =>  $merchantId,
            'experiment_id' => $this->app['config']['trace.app.experiments.phantom_oauth_onboarding_exp_id']
        ];

        return $this->app['splitz']->isExperimentEnabled($properties);
    }

    private function isMerchantOnboardingEnabledForScope($scopes) : bool
    {
        if (!empty($scopes) && (in_array($scopes[0]['id'], self::SCOPES_ALLOWED_FOR_ONBOARDING)))
        {
            return true;
        }
        return false;
    }

    private function addCustomPolicyIfApplicable(array & $scopePolicies, array $scopes, array $input): void
    {
        // don't add the custom TnC url if both read_only & read_write scopes doesn't exist
        if (empty($input['platform_fee_policy_url']) === true or
            (in_array(ScopeConstants::READ_ONLY, $scopes) === false and
            in_array(ScopeConstants::READ_WRITE, $scopes) === false))
        {
            return;
        }

        $scopePolicies[Constant::CUSTOM_POLICY] =  $input['platform_fee_policy_url'];
    }
}
