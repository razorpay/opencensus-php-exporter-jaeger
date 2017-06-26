<?php

namespace App\Models\Auth;

use Trace;
use Requests;
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

        (new Auth\Validator)->validateInput('authorize', $input);

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

        try
        {
            return $this->oauthServer->getAuthCode($queryParamsArray, $data);
        }
        catch (\Exception $ex)
        {
            Trace::error(TraceCode::AUTH_AUTHORIZE_FAILURE, [$ex->getMessage()]);
        }
    }

    public function generateAccessToken(array $input)
    {
        (new Auth\Validator)->validateInput('access_token', $input);

        $data = $this->oauthServer->getAccessToken($input);

        return json_decode($data->getBody(), true);
    }

    protected function resolveTokenOnDashboard(string $token)
    {
        $options = ['auth' => ['rzp_oauth', env('APP_DASHBOARD_SECRET')]];

        $response = Requests::get(
            env('APP_DASHBOARD_URL') . '/oauth_token/' . $token,
            [],
            $options
        );

        $body = json_decode($response->body, true);

        // TODO: null/fail check

        return $body['data'];
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
