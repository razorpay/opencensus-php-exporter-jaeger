<?php

namespace App\Models\Token;

use App\Error\ErrorCode;
use App\Exception\BadRequestException;
use App\Models\Auth;
use Razorpay\OAuth;
use Razorpay\OAuth\Token as OauthToken;
use Razorpay\OAuth\Client;

class Service
{
    protected $service;

    public function __construct()
    {
        $this->service = new OauthToken\Service;

        $this->validator = new Validator;

        $this->oauthServer = new OAuth\OAuthServer(env('APP_ENV'), new Auth\Repository);
    }

    public function validateRevokeTokenRequest($input)
    {
        $this->validator->validateInput('revoke_by_partner', $input);

        //validate client credentials
        (new Client\Repository)->getClientEntity(
            $input['client_id'],
            "",
            $input['client_secret'],
            true
        );

        // validate if the access token is a valid one
        $response = $this->oauthServer->authenticateWithBearerToken($input['token']);

        // validate whether token passed is issued to the client
        if ($response['client_id'] != $input['client_id'])
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_INVALID_CLIENT);
        }

        return $response;
    }

    // get refresh tokens for the given access token
    // if not empty, revoke the refresh tokens
    public function revokeRefreshToken($token){

        $ids = (new OAuth\RefreshToken\Repository)->fetchIdForToken($token['id']);

        if(!empty($ids))
        {
            foreach ($ids as $id)
            {
                (new OAuth\RefreshToken\Repository)->revokeRefreshToken($id);
            }
        }
    }
}
