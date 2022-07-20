<?php

namespace App\Trace\Metrics;

use App\Constants\Metric;
use App\Tests\Unit\UnitTestCase;
use Illuminate\Support\Facades\Config;
use Mockery;

class DimensionsProcessorTest extends UnitTestCase
{

    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * process should override values of dimensions based on whitelisted values.
     * @return void
     */
    public function testDimensionsProcessorProcess()
    {
        $whiteListedLabels = [
            'merchant_id' => ['xyz_merchant']
        ];

        Config::shouldReceive('get')
            ->withArgs(['trace.cache', NULL])
            ->andReturn('tmp')
            ->shouldReceive('get')
            ->with('trace.cloud', NULL)
            ->andReturn(false)
            ->shouldReceive('get')
            ->with('metrics.default_label_value', NULL)
            ->andReturn(Metric::LABEL_DEFAULT_VALUE)
            ->shouldReceive('get')
            ->with('metrics.whitelisted_label_values', NULL)
            ->andReturn($whiteListedLabels);
        Config::partialMock();

        Mockery::mock('overload:Razorpay\EC2Metadata\Ec2MetadataGetter')
            ->shouldReceive('allowDummy')
            ->andReturn()
            ->shouldReceive('getMultiple')
            ->with(['LocalIpv4'])
            ->andReturn(['LocalIpv4' => '10.123.123.123']);

        $dimensionProcessor = new DimensionsProcessor();
        $dimension = $dimensionProcessor->process(['merchant_id' => 'some_other_merchant', 'client_id' => 'dummy_client', 'state' => '']);
        $this->assertEquals([
            'merchant_id' => 'other',
            'client_id' => 'dummy_client',
            'instance' => '10.123.123.123',
            'state' => 'other',
        ], $dimension);
    }

}
