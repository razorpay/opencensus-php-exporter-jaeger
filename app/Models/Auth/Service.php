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

        $scopeData = [
            'scopes' => []
        ];

        $authorizeData = array_merge($appData, $scopeData);

        $authorizeData['dashboard_url'] = env('APP_DASHBOARD_URL');

        return $authorizeData;
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
            throw new BadRequestException(ErrorCode::BAD_REQUEST_ERROR);
        }

        $application = $client->application;

        $data = [
            'name' => $application->getName(),
            'url'  => $application->getWebsite(),
            'logo' => $application->getLogoUrl()
        ];

        return ['application' => $data];
    }
}
