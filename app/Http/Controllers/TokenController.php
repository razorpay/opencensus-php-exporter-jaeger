<?php

namespace App\Http\Controllers;

use App\Constants\Application;
use App\Constants\Metric;
use App\Constants\TraceCode;
use App\Exception\LogicException;
use App\Models\Auth;
use App\Models\Token as AuthToken;
use Razorpay\OAuth\RefreshToken;
use Razorpay\OAuth\Token;
use Request;
use Trace;

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

    /**
     * @throws \Exception
     */
    public function revoke(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::REVOKE_TOKEN_REQUEST, compact('input', 'id'));

        $token = (new Token\Repository)->findOrFailPublic($id);

        $this->oauthTokenService->revokeAccessToken($id, $input);

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function revokeTokensForMobileApp() :\Illuminate\Http\JsonResponse
    {
        $input = Request::all();

        Trace::info(TraceCode::REVOKE_TOKEN_FOR_MOBILE_APP, $input);

        $params = [
            Application::CLIENT_ID   => $input[Application::CLIENT_ID],
            Application::MERCHANT_ID => $input[Application::MERCHANT_ID],
            Application::USER_ID     => $input[Application::USER_ID],
        ];

        $tokens = $this->oauthTokenService->getAllTokensForMobileApp($params);

        $this->revokeAccessTokensForMobile($tokens);

        return response()->json([Application::MESSAGE => Application::TOKEN_REVOKED]);
    }

    /**
     * Revoke access token for mobile
     * @param $tokens
     * @return void
     */
    private function revokeAccessTokensForMobile($tokens) :void
    {
        foreach ($tokens[Application::ITEMS] as $token)
        {
            // Revoke access tokens
            // with scope x_mobile_app
            if ($token[Application::TYPE] === Application::ACCESS_TOKEN
                && count($token[Application::SCOPES]) === 1
                && $token[Application::SCOPES][0] === Application::X_MOBILE_APP)
            {
                app('trace')->count(Metric::REVOKE_TOKEN_MOBILE_APP_MERCHANT_USER_COUNT);

                $this->authServerTokenService->handleRevokeTokenRequestForMobileApp(
                    $token[Application::ID], [Application::MERCHANT_ID => $token[Application::MERCHANT_ID]]);
            }
        }
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
