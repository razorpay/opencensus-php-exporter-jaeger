<?php

namespace App\Http\Controllers;

use Trace;
use Request;

use Razorpay\OAuth\Token;
use Razorpay\OAuth\RefreshToken;

use App\Models\Auth;
use App\Models\Token as AuthToken;
use App\Constants\TraceCode;
use App\Exception\LogicException;

class TokenController extends Controller
{
    protected $oauthTokenService;

    protected $authServerTokenService;

    protected $oauthRefreshTokenService;


    const APPLICATION_ID      = 'application_id';
    const PARTNER_MERCHANT_ID = 'partner_merchant_id';
    const SUB_MERCHANT_ID     = 'sub_merchant_id';
    /**
     * @var RefreshToken\Service
     */

    public function __construct()
    {
        $this->oauthTokenService         = new Token\Service;
        $this->oauthRefreshTokenService  = new RefreshToken\Service;
        $this->authServerTokenService    = new AuthToken\Service;
    }

    public function getAll()
    {
        $input = Request::all();

        Trace::info(TraceCode::GET_TOKENS_REQUEST, $input);

        $tokens = $this->oauthTokenService->getAllTokens($input);

        return response()->json($tokens);
    }

    public function get(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::GET_TOKEN_REQUEST, compact('input', 'id'));

        $token = $this->oauthTokenService->getToken($id, $input);

        return response()->json($token);
    }

    public function validatePublicToken(string $id)
    {
        Trace::info(TraceCode::VALIDATE_PUBLIC_TOKEN_REQUEST, ["id" => $id]);

        $found = (preg_match("/^rzp_(test|live)_oauth_([a-zA-Z0-9]{14})$/", $id, $matches) === 1);
        if ($found === false)
        {
            throw new LogicException("public token is invalid");
        }

        $token = (new Auth\Repository)->findByPublicTokenIdAndMode($matches[2], $matches[1]);
        if ($token === null)
        {
            return response()->json(["exist"=> false]);
        }

        return response()->json(["exist"=> true]);
    }

    public function revoke(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::REVOKE_TOKEN_REQUEST, compact('input', 'id'));

        $token = (new Token\Repository)->findOrFailPublic($id);

        $this->oauthTokenService->revoketoken($id, $input);

        $this->revokeMerchantApplicationMapping($token, $input);

        return response()->json([]);
    }

    /**
     * Revoke a token by partner apps takes client and client secret as a parameter for validation
     * and takes token_type_hint (access_token or refresh_token) as a parameter to identify the token to be revoked
     *
     */
    public function revokeByPartner()
    {
        $input = Request::all();

        Trace::info(TraceCode::REVOKE_TOKEN_BY_PARTNER);

        $this->authServerTokenService->handleRevokeTokenRequest($input);

        return response()->json(['message' => 'Token Revoked']);
    }

    /**
     * This is to revoke token for a merchant user pair in mobile app
     * Used for forgot password and removal of user from the team
     */
    public function revokeTokensForMobileApp()
    {
        $input = Request::all();

        Trace::info(TraceCode::REVOKE_TOKEN_FOR_MOBILE_APP, $input);

        $params = [
            'client_id' => $input['client_id'],
            'merchant_id' => $input['merchant_id'],
            'user_id' => $input['user_id'],
        ];

        $tokens = $this->oauthTokenService->getAllTokensForMobileApp($params);

        foreach ($tokens["items"] as $token)
        {
            if ($token['type'] === 'access_token' && count($token['scopes']) === 1 && $token['scopes'][0] === 'x_mobile_app')
            {
                $this->authServerTokenService->handleRevokeTokenRequestForMobileApp($token['id'], ['merchant_id' => $token['merchant_id']]);
            }
        }

        return response()->json(['message' => 'Token Revoked']);
    }

    public function createForPartner()
    {
        $input = Request::all();

        $token = $this->oauthTokenService->createPartnerToken(
            $input[self::APPLICATION_ID],
            $input[self::PARTNER_MERCHANT_ID],
            $input[self::SUB_MERCHANT_ID]);

        return response()->json($token);
    }

    protected function revokeMerchantApplicationMapping(Token\Entity $token, array $input)
    {
        $merchantId = $input[Token\Entity::MERCHANT_ID];

        //
        // We get the application id from the token client and then fetch all tokens
        // of all clients of that app. If no such token is active then we delete
        // the mapping on API side from merchant_access_map table so that flows like
        // webhooks won't fire for apps that don't have any more active tokens.
        //
        $client = $token->client;

        $appId = $client->getApplicationId();

        $allActiveAppTokens = (new Token\Repository)->fetchAccessTokensByAppAndMerchant($appId, $merchantId);

        if (count($allActiveAppTokens) > 0)
        {
            return;
        }

        $apiService = (new Auth\Service)->getApiService();

        $apiService->revokeMerchantApplicationMapping($appId, $merchantId);
    }
}
