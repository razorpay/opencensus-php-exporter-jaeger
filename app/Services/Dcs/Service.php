<?php

namespace App\Services\Dcs;

use Trace;
use App\Constants\Mode;
use Razorpay\Dcs\Cache;
use Razorpay\Dcs\Client;
use App\Constants\Metric;
use App\Constants\TraceCode;
use Razorpay\Dcs\DataFormatter;
use Razorpay\Dcs\Config\Config;
use App\Services\Dcs\Features\Constants;
use Razorpay\Dcs\Config\UserCredentials;
use Razorpay\Dcs\Kv\V1\Model\V1GetResponse;

class Service
{
    protected mixed $config;

    protected mixed $client = null;

    public function __construct()
    {
        $this->config = app('config')['trace.services.dcs'];
    }

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getClient(string $mode): Client
    {
        if (empty($this->client) === true)
        {
            $this->initializeClientWithMode($mode);
        }

        return $this->client;
    }

    /**
     * This function checks if a feature is enabled for any of the given entity ids. Using the response received from
     * DCS client, it checks whether the given feature is present in the list of features enabled for any entity id
     * 
     * @throws \Throwable
     */
    public function isFeatureEnabledForEntityIdsAndFeatureName(array $entityIds, string $featureName, $mode = Mode::LIVE): bool
    {
        try
        {
            $response = $this->fetchFeatureStatusForEntityIds($entityIds, $featureName, $mode);

            if (empty($response) === true)
            {
                return false;
            }

            $kvs = $response->getKvs();

            if (empty($kvs) === true)
            {
                return false;
            }

            foreach ($kvs as $kv)
            {
                $key = $kv->getKey();

                $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

                if ($features[$featureName] === true)
                {
                    return true;
                }
            }

            return false;
        }
        catch (\Throwable $e)
        {
            $errorData = ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()];

            Trace::error(
                TraceCode::DCS_INTEGRATION_ERROR,
                array_merge(['entity_ids' => $entityIds, 'feature' => $featureName, 'mode' => $mode], $errorData)
            );

            throw $e;
        }
    }

    public function fetchFeatureStatusForEntityIds(array $entityIds, string $featureName, string $mode): V1GetResponse
    {
        $traceData =   [
            'entity_ids'    => $entityIds,
            'feature'       => $featureName,
            'mode'          => $mode,
        ];

        app('trace')->count(Metric::DCS_REQUESTS_TOTAL);

        Trace::info(TraceCode::DCS_REQUEST, $traceData);

        (new Validator())->validateMode($mode);

        $key = Constants::$featureToDcsKeyMapping[$featureName];

        $data = DataFormatter::toKeyMapWithOutId($key);

        $response = $this->getClient($mode)->fetchMultiple($data, $entityIds, [$featureName]);

        Trace::info(TraceCode::DCS_RESPONSE, array_merge($traceData, ['response' => json_decode($response, true)]));

        return $response;
    }

    protected function initializeClientWithMode($mode): void
    {
        (new Validator())->validateMode($mode);

        $creds  = new UserCredentials();
        $cache  = new Cache();
        $config = new Config($cache);

        $creds->setUsername($this->config[$mode]['username'])
              ->setPassword($this->config[$mode]['password']);

        $config->setServerURL($this->config[$mode]['url'])
               ->setMock($this->config['mock'])
               ->setUserCreds($creds)
               ->setMode($mode);

        $this->setClient(new Client($config));
    }
}
