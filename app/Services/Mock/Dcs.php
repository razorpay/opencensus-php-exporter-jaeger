<?php

namespace App\Services\Mock;

use App\Constants\Mode;
use Razorpay\Dcs\Cache;
use Razorpay\Dcs\Config\Config;
use App\Services\Dcs\Features\Constants;
use Razorpay\Dcs\Config\UserCredentials;
use App\Services\Dcs\Service as DcsService;

class Dcs extends DcsService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initializeClientWithMode($mode): void
    {
        $creds  = new UserCredentials();
        $cache  = new Cache();
        $config = new Config($cache);

        $creds->setUsername($this->config[$mode]['username'])
              ->setPassword($this->config[$mode]['password']);

        $config->setServerURL($this->config[$mode]['url'])
               ->setMock($this->config['mock'])
               ->setUserCreds($creds)
               ->setMode($mode);

        $this->client = \Mockery::mock('Razorpay\Dcs\Client', [$config])->makePartial();
    }

    /**
     * @throws \Throwable
     */
    public function isFeatureEnabledForEntityIdsAndFeatureName(array $entityIds, string $featureName, $mode = Mode::LIVE): bool
    {
        if (in_array('LNWDzDK1sqQnjY', $entityIds) === true or in_array('LNWDzDK1sqQnjZ', $entityIds) === true)
        {
            return parent::isFeatureEnabledForEntityIdsAndFeatureName($entityIds, $featureName, $mode);
        }
        else if ($featureName === Constants::ENABLE_PARTNER_PLAT_FEE_INVOICE and in_array('20000000000002', $entityIds) === true)
        {
            return false;
        }

        return true;
    }
}
