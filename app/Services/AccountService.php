<?php

namespace App\Services;

use Accounts\Account\V1\GetByIdRequest;
use App\Tests\Functional\AuthController\ClientCredentials;
use Google\Protobuf\FieldMask;
use Google\Protobuf\StringValue;
use Razorpay\Asv\Client;
use Razorpay\Asv\Config\Config;
use Razorpay\Asv\DbSource;
use Razorpay\Asv\Interfaces\AccountInterface;
use Razorpay\Asv\RequestMetadata;

class AccountService {

    protected Config $config ;

    protected AccountInterface $account;

    protected string $host ;

    protected string $clientId ;

    protected string $clientSecret ;

    protected Client $client ;

    public function __construct() {

        $this->host = env('APP_ASV_HOST');
        $this->clientId = env('APP_ASV_CLIENT_ID');
        $this->clientSecret = env('APP_ASV_CLIENT_SECRET');

        $clientCredentials = new \Razorpay\Asv\Config\ClientCredentials($this->clientId, $this->clientSecret);

        $this->config = (new Config())
            ->setHost($this->host)
            ->setClientCredentials($clientCredentials);

        $this->client = new Client($this->config) ;

        $this->account = $this->client->getAccount() ;

    }
    public function getAccountById(string $id) : array
    {
        $metadata  = (new RequestMetadata())
            ->setSourceDatabase(DbSource::ApiMaster)
            ->setHeaders([]);

        $request = new GetByIdRequest();

        $request->setId($id);
        $request->setFieldMask(new FieldMask([
            'paths' => [
                "account.name",
                "account.email",
                "account.country_code"
            ]
        ]));

        return $this->account->GetAccountById($request, $metadata);

    }

    public function getCountryCode(string $merchantId): ?string
    {
        $account = $this->getAccountById($merchantId);

        if (empty($account)) {
            return null;
        }

        $firstAccount = reset($account);

        if (!$firstAccount || !method_exists($firstAccount, 'getAccount')) {
            return null;
        }

        $accountObj = $firstAccount->getAccount();

        if (!$accountObj || !method_exists($accountObj, 'getCountryCode')) {
            return null;
        }

        $countryCode = $accountObj->getCountryCode();

        return $countryCode instanceof StringValue ? $countryCode->getValue() : null;
    }

}
