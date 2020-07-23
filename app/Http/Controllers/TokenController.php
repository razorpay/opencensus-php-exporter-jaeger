<?php

namespace App\Http\Controllers;

use Trace;
use Request;
use Razorpay\OAuth\Token;
use App\Constants\TraceCode;

use App\Models\Auth;

class TokenController extends Controller
{
    protected $service;

    const APPLICATION_ID      = 'application_id';
    const PARTNER_MERCHANT_ID = 'partner_merchant_id';
    const SUB_MERCHANT_ID     = 'sub_merchant_id';

    public function __construct()
    {
        $this->service = new Token\Service;
    }

    public function getAll()
    {
        $input = Request::all();

        Trace::info(TraceCode::GET_TOKENS_REQUEST, $input);

        $tokens = $this->service->getAllTokens($input);

        return response()->json($tokens);
    }

    public function get(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::GET_TOKEN_REQUEST, compact('input', 'id'));

        $token = $this->service->getToken($id, $input);

        return response()->json($token);
    }

    public function revoke(string $id)
    {
        $input = Request::all();

        Trace::info(TraceCode::REVOKE_TOKEN_REQUEST, compact('input', 'id'));

        $token = (new Token\Repository)->findOrFailPublic($id);

        $this->service->revoketoken($id, $input);

        $this->revokeMerchantApplicationMapping($token, $input);

        return response()->json([]);
    }

    public function createForPartner()
    {
        $input = Request::all();

        $token = $this->service->createPartnerToken(
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
