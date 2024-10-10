<?php

namespace Unit\Models\Auth;

use ReflectionException;
use Razorpay\OAuth\Scope;
use App\Models\Auth\Service;
use Razorpay\OAuth\OAuthServer;
use App\Tests\Unit\UnitTestCase;
use Razorpay\OAuth\Scope\ScopeConstants;

class ServiceTest extends UnitTestCase
{
    /**
     * @throws ReflectionException
     */
    public function testParseScopePolicies()
    {
        $class            = new \ReflectionClass('App\Models\Auth\Service');
        $scopeToPolicyMap = $class->getConstant("SCOPE_TO_POLICY_MAP");
        $method           = $class->getMethod("parseScopePolicies");

        // test read_only
        $expected = $scopeToPolicyMap[ScopeConstants::READ_ONLY];
        $actual   = $method->invokeArgs(
            (new Service()), [[ScopeConstants::READ_ONLY]]
        );

        $this->assertEquals($expected, $actual);

        // test read_write
        $expected = $scopeToPolicyMap[ScopeConstants::READ_WRITE];
        $actual   = $method->invokeArgs(
            (new Service()), [[ScopeConstants::READ_WRITE]]
        );

        $this->assertEquals($expected, $actual);

        // test read_only and rx_read_only
        $expected = $scopeToPolicyMap[ScopeConstants::READ_ONLY] + $scopeToPolicyMap[ScopeConstants::RX_READ_ONLY];
        $actual   = $method->invokeArgs(
            (new Service()), [[ScopeConstants::RX_READ_ONLY, ScopeConstants::READ_ONLY]]
        );

        $this->assertEquals($expected, $actual);

        // test read_only and read_write
        $expected = $scopeToPolicyMap[ScopeConstants::READ_WRITE];
        $actual   = $method->invokeArgs(
            (new Service()), [[ScopeConstants::READ_WRITE, ScopeConstants::READ_ONLY]]
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws ReflectionException
     */
    public function testParseScopeDescriptionsForDisplay()
    {
        $class  = new \ReflectionClass('App\Models\Auth\Service');
        $method = $class->getMethod("parseScopeDescriptionsForDisplay");

        $scope = new Scope\Entity();
        $scope->setIdentifier(ScopeConstants::READ_ONLY);

        // test read_only
        $expected = OAuthServer::$scopes[ScopeConstants::READ_ONLY];
        $actual   = $method->invokeArgs(
            (new Service()),
            [
                collect(
                    [
                        $scope,
                    ]
                )
            ]
        );

        $this->assertEquals($expected, $actual);

        $scope = new Scope\Entity();
        $scope->setIdentifier(ScopeConstants::READ_WRITE);

        // test read_write
        $expected = OAuthServer::$scopes[ScopeConstants::READ_WRITE];
        $actual   = $method->invokeArgs(
            (new Service()),
            [
                collect(
                    [
                        $scope,
                    ]
                )
            ]
        );

        $this->assertEquals($expected, $actual);

        $readOnlyScope = new Scope\Entity();
        $readOnlyScope->setIdentifier(ScopeConstants::READ_ONLY);

        $rxReadOnlyScope = new Scope\Entity();
        $rxReadOnlyScope->setIdentifier(ScopeConstants::RX_READ_ONLY);


        $readWriteScope = new Scope\Entity();
        $readWriteScope->setIdentifier(ScopeConstants::READ_WRITE);

        // test read_only and rx_read_only
        $expected = array_collapse(
            [
                OAuthServer::$scopes[ScopeConstants::READ_ONLY], OAuthServer::$scopes[ScopeConstants::RX_READ_ONLY]
            ]
        );
        $actual   = $method->invokeArgs(
            (new Service()),
            [
                collect(
                    [
                        $readOnlyScope,
                        $rxReadOnlyScope
                    ]
                )
            ]
        );

        $this->assertEquals($expected, $actual);

        // test read_only and read_write
        $expected = OAuthServer::$scopes[ScopeConstants::READ_WRITE];
        $actual   = $method->invokeArgs(
            (new Service()),
            [
                collect(
                    [
                        $readOnlyScope,
                        $readWriteScope
                    ]
                )
            ]
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * @throws ReflectionException
     */
    public function testFetchCustomPolicyUrlForApplication()
    {
        config(['trace.services.splitz.mock' => true]);

        $class  = new \ReflectionClass('App\Models\Auth\Service');

        $method = $class->getMethod("fetchCustomPolicyUrlForApplication");

        $expected = 'https://www.xyz.com/terms';

        $actual   = $method->invokeArgs((new Service()), ['randomMid', 'randomAppId', [ScopeConstants::READ_ONLY]]);

        $this->assertEquals($expected, $actual);
    }

    public function testAddCustomPolicyIfApplicable()
    {
        $class  = new \ReflectionClass('App\Models\Auth\Service');

        $method = $class->getMethod("addCustomPolicyIfApplicable");

        $scopePolicies = ['App Policies' => 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/'];

        $input = ['custom_policy_url' =>  'https://www.xyz.com/terms'];

        $method->invokeArgs((new Service()), [&$scopePolicies, [ScopeConstants::READ_WRITE], $input]);

        $expectedScopePolicies = [
            'App Policies'  => 'https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/',
            'Custom Policy' => 'https://www.xyz.com/terms'
        ];

        $this->assertEquals($expectedScopePolicies, $scopePolicies);
    }
}
