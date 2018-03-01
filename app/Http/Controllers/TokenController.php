<?php

namespace App\Http\Controllers;

use Request;
use Razorpay\OAuth\Token;
use Razorpay\OAuth\Client;

use App\Models\Auth;

class TokenController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new Token\Service;
    }

    public function getAll()
    {
        $input = Request::all();

        $tokens = $this->service->getAllTokens($input);

        return response()->json($tokens);
    }

    public function get(string $id)
    {
        $input = Request::all();

        $token = $this->service->getToken($id, $input);

        return response()->json($token);
    }

    public function revoke(string $id)
    {
        $input = Request::all();

        $token = (new Token\Repository)->findOrFailPublic($id);

        $this->service->revoketoken($id, $input);

        $this->revokeMerchantApplicationMapping($token, $input);

        return response()->json([]);
    }

    protected function revokeMerchantApplicationMapping(Token\Entity $token, array $input)
    {
        $merchantId = $input[Token\Entity::MERCHANT_ID];

        // We get the application id from the token client and then fetch all tokens
        // of all clients of that app. If no such token is active then we delete
        // the mapping on API side from merchant_access_map table so that flows like
        // webhooks won't fire for apps that don't have any more ctive tokens.

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
