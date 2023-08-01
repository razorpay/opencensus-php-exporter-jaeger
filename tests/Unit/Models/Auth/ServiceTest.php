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

        // test read_only
        $expected = OAuthServer::$scopes[ScopeConstants::READ_ONLY];
        $actual   = $method->invokeArgs(
            (new Service()),
            [
                collect(
                    [
                        new Scope\Entity(ScopeConstants::READ_ONLY),
                    ]
                )
            ]
        );

        $this->assertEquals($expected, $actual);

        // test read_write
        $expected = OAuthServer::$scopes[ScopeConstants::READ_WRITE];
        $actual   = $method->invokeArgs(
            (new Service()),
            [
                collect(
                    [
                        new Scope\Entity(ScopeConstants::READ_WRITE),
                    ]
                )
            ]
        );

        $this->assertEquals($expected, $actual);

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
                        new Scope\Entity(ScopeConstants::READ_ONLY),
                        new Scope\Entity(ScopeConstants::RX_READ_ONLY)
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
                        new Scope\Entity(ScopeConstants::READ_ONLY),
                        new Scope\Entity(ScopeConstants::READ_WRITE)
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
        $class  = new \ReflectionClass('App\Models\Auth\Service');

        $method = $class->getMethod("fetchCustomPolicyUrlForApplication");

        $expected = 'https://www.xyz.com/terms';

        $actual   = $method->invokeArgs((new Service()), ['randomAppId', [ScopeConstants::READ_ONLY]]);

        $this->assertEquals($expected, $actual);
    }
}
