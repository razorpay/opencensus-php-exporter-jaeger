<?php

namespace Unit\Service;

use App\Constants\Mode;
use Razorpay\Dcs\DataFormatter;
use App\Tests\Unit\UnitTestCase;
use Razorpay\Dcs\Kv\V1\Model\V1Key;
use Razorpay\Dcs\Kv\V1\ApiException;
use App\Services\Dcs\Features\Constants;
use Razorpay\Dcs\Kv\V1\Model\V1KeyValue;
use Razorpay\Dcs\Kv\V1\Model\V1GetResponse;

class DcsServiceTest extends UnitTestCase
{
    protected mixed $dcsService;

    public function setUp(): void
    {
        parent::setUp();

        config(['trace.services.dcs.mock' => true]);

        $this->dcsService = $this->app['dcs'];
    }

    public function testFetchByEntityIdsAndNameWithFeatureEnabled()
    {
        $testMockClient = $this->dcsService->getClient(Mode::TEST);

        $testMockClient->shouldReceive('fetchMultiple')
                       ->times(1)
                       ->andReturnUsing(
                            function (array $data, array $entityIds, array $fields) {
                                $res = new V1GetResponse();
                                $kvs = [];
                                foreach ($entityIds as $id) {
                                    $key = (new V1Key())->setNamespace($data[\Razorpay\Dcs\Constants::NAMESPACE])
                                                        ->setEntity($data[\Razorpay\Dcs\Constants::ENTITY])
                                                        ->setEntityId($id)
                                                        ->setDomain($data[\Razorpay\Dcs\Constants::DOMAIN])
                                                        ->setObjectName($data[\Razorpay\Dcs\Constants::OBJECT_NAME]);

                                    $kv = new V1KeyValue();
                                    $kv->setKey($key);
                                    $kv->setValue("CAE=");
                                    $kvs[] = $kv;
                                }

                                $res->setKvs($kvs);
                                return $res;
                            }
                       );

        $res = $this->dcsService->isFeatureEnabledForEntityIdsAndFeatureName(['LNWDzDK1sqQnjY', 'LNWDzDK1sqQnjZ'], Constants::ENABLE_ROUTE_PARTNERSHIPS, Mode::TEST);

        $this->assertTrue($res);
    }

    public function testFetchByEntityIdsAndNameWithFeatureNotEnabled()
    {
        $testMockClient = $this->dcsService->getClient(Mode::TEST);

        $testMockClient->shouldReceive('fetchMultiple')
                       ->times(1)
                       ->andReturnUsing(
                            function (array $data, array $entityIds, array $fields) {
                                $res = new V1GetResponse();
                                $kvs = [];
                                foreach ($entityIds as $id) {
                                    $key = (new V1Key())->setNamespace($data[\Razorpay\Dcs\Constants::NAMESPACE])
                                        ->setEntity($data[\Razorpay\Dcs\Constants::ENTITY])
                                        ->setEntityId($id)
                                        ->setDomain($data[\Razorpay\Dcs\Constants::DOMAIN])
                                        ->setObjectName($data[\Razorpay\Dcs\Constants::OBJECT_NAME]);

                                    $kv = new V1KeyValue();
                                    $kv->setKey($key);
                                    $kv->setValue("");
                                    $kvs[] = $kv;
                                }

                                $res->setKvs($kvs);
                                return $res;
                            }
                      );

        $res = $this->dcsService->isFeatureEnabledForEntityIdsAndFeatureName(['LNWDzDK1sqQnjY', 'LNWDzDK1sqQnjZ'], Constants::ENABLE_ROUTE_PARTNERSHIPS, Mode::TEST);

        $this->assertFalse($res);
    }

    public function testFetchByEntityIdsAndNameThrowingError()
    {
        $testMockClient = $this->dcsService->getClient(Mode::TEST);

        $testMockClient->shouldReceive('fetchMultiple')
                       ->times(1)
                       ->andReturnUsing(
                            function (array $data, array $entityIds, array $fields) {
                                throw new ApiException(
                                    "Unauthorized Error",
                                    403
                                );
                            }
                       );

        $this->expectException("Razorpay\Dcs\Kv\V1\ApiException");
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("Unauthorized Error");
        $this->dcsService->isFeatureEnabledForEntityIdsAndFeatureName(["LNWDzDK1sqQnjY"], Constants::ENABLE_ROUTE_PARTNERSHIPS, Mode::TEST);
    }

    public function testFetchFeatureStatusForEntityIdsWithFeatureEnabled()
    {
        $testMockClient = $this->dcsService->getClient(Mode::TEST);

        $testMockClient->shouldReceive('fetchMultiple')
                       ->times(1)
                       ->andReturnUsing(
                            function (array $data, array $entityIds, array $fields) {
                                $res = new V1GetResponse();
                                $kvs = [];
                                foreach ($entityIds as $id) {
                                    $key = (new V1Key())->setNamespace($data[\Razorpay\Dcs\Constants::NAMESPACE])
                                        ->setEntity($data[\Razorpay\Dcs\Constants::ENTITY])
                                        ->setEntityId($id)
                                        ->setDomain($data[\Razorpay\Dcs\Constants::DOMAIN])
                                        ->setObjectName($data[\Razorpay\Dcs\Constants::OBJECT_NAME]);

                                    $kv = new V1KeyValue();
                                    $kv->setKey($key);
                                    $kv->setValue("CAE=");
                                    $kvs[] = $kv;
                                }

                                $res->setKvs($kvs);
                                return $res;
                            }
                       );

        $res = $this->dcsService->fetchFeatureStatusForEntityIds(['LNWDzDK1sqQnjY', 'LNWDzDK1sqQnjZ'], Constants::ENABLE_ROUTE_PARTNERSHIPS, Mode::TEST);

        $kvs = $res->getKvs();

        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();

            $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

            $this->assertTrue($features[Constants::ENABLE_ROUTE_PARTNERSHIPS]);
        }
    }

    public function testFetchFeatureStatusForEntityIdsWithFeatureDisabled()
    {
        $testMockClient = $this->dcsService->getClient(Mode::TEST);

        $testMockClient->shouldReceive('fetchMultiple')
            ->times(1)
            ->andReturnUsing(
                function (array $data, array $entityIds, array $fields) {
                    $res = new V1GetResponse();
                    $kvs = [];
                    foreach ($entityIds as $id) {
                        $key = (new V1Key())->setNamespace($data[\Razorpay\Dcs\Constants::NAMESPACE])
                            ->setEntity($data[\Razorpay\Dcs\Constants::ENTITY])
                            ->setEntityId($id)
                            ->setDomain($data[\Razorpay\Dcs\Constants::DOMAIN])
                            ->setObjectName($data[\Razorpay\Dcs\Constants::OBJECT_NAME]);

                        $kv = new V1KeyValue();
                        $kv->setKey($key);
                        $kv->setValue("");
                        $kvs[] = $kv;
                    }

                    $res->setKvs($kvs);
                    return $res;
                }
            );

        $res = $this->dcsService->fetchFeatureStatusForEntityIds(['LNWDzDK1sqQnjY', 'LNWDzDK1sqQnjZ'], Constants::ENABLE_ROUTE_PARTNERSHIPS, Mode::TEST);

        $kvs = $res->getKvs();

        foreach ($kvs as $kv)
        {
            $key = $kv->getKey();

            $features = DataFormatter::unMarshal($kv->getValue(), DataFormatter::convertDCSKeyToClassName($key));

            $this->assertFalse($features[Constants::ENABLE_ROUTE_PARTNERSHIPS]);
        }
    }

    public function testFetchFeatureStatusForEntityIdsThrowingError()
    {
        $testMockClient = $this->dcsService->getClient(Mode::TEST);

        $testMockClient->shouldReceive('fetchMultiple')
                       ->times(1)
                       ->andReturnUsing(
                            function (array $data, array $entityIds, array $fields) {
                                throw new ApiException(
                                    "Unauthorized Error",
                                    403
                                );
                            }
                       );

        $this->expectException("Razorpay\Dcs\Kv\V1\ApiException");
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage("Unauthorized Error");
        $this->dcsService->isFeatureEnabledForEntityIdsAndFeatureName(["LNWDzDK1sqQnjY"], Constants::ENABLE_ROUTE_PARTNERSHIPS, Mode::TEST);
    }

    public function testInitializeClientWithMode()
    {
        $this->dcsService->initializeClientWithMode(Mode::TEST);

        $testMockClient = $this->dcsService->getClient(Mode::TEST);

        $this->assertNotNull($testMockClient);
    }
}
