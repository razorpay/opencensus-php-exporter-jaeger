<?php

namespace Unit\Service;

use App\Constants\Metric;
use App\Constants\TraceCode;
use App\Exception\InvalidArgumentException;
use App\Services\SignerCache;
use App\Tests\Unit\UnitTestCase;
use Exception;
use Illuminate\Redis\Connections\PredisConnection;
use Mockery;
use Predis\Response\ServerException;
use Razorpay\Trace\Facades\Trace;
use Razorpay\Trace\Logger;
use ReflectionClass;

class SignerCacheTest extends UnitTestCase
{
    const CACHE_KEY_PREFIX = "credcase:ks";
    const CACHE_KEY_VERSION = "v1";

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @Test
     * testWriteCredentials validates if the write function was called with the correct parameters
     * and appropriate logs and metrics are emitted
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws Exception
     */
    public function testWriteCredentials(): void
    {
        $key = "rzp_test_oauth_PZgJKbhpb9jbXZ";
        $rawSecret = "thisissupersecret";
        $encryptedSecret = "encryptedSecret";
        $ttlInSeconds = 60;

        $sc = $this->getMockBuilder(SignerCache::class)->onlyMethods(['encrypt', 'writeToCacheWithRetry'])->getMock();

        Trace::shouldReceive('info')
            ->once()
            ->withArgs([TraceCode::SIGNER_CACHE_CREATE_CREDENTIALS, [
                'key' => $key,
            ]]);

        $sc->expects($this->once())->method('encrypt')->with($rawSecret)->willReturn($encryptedSecret);
        $sc->expects($this->once())->method('writeToCacheWithRetry')->with($key, $encryptedSecret, $ttlInSeconds);

        Trace::shouldReceive('histogram')
            ->withArgs([Metric::SIGNER_CACHE_REQUEST_DURATION_SECONDS, Mockery::any(), [
                Metric::LABEL_STATUS => true,
                Metric::LABEL_ATTEMPTS  => 0,
            ]])
            ->once();

        $sc->writeCredentials($key, $rawSecret, $ttlInSeconds);
    }

    /**
     * @Test
     * testWriteCredentialsFails tests that an exception is thrown when write function fails
     * Also verifies if appropriate logs and metrics are emitted
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws Exception
     */
    public function testWriteCredentialsFails(): void
    {
        $key = "rzp_test_oauth_PZgJKbhpb9jbXZ";
        $rawSecret = "thisissupersecret";
        $encryptedSecret = "encryptedSecret";
        $ttlInSeconds = 60;
        $sc = $this->getMockBuilder(SignerCache::class)->onlyMethods(['encrypt', 'writeToCacheWithRetry'])->getMock();

        Trace::shouldReceive('info')
            ->once()
            ->withArgs([TraceCode::SIGNER_CACHE_CREATE_CREDENTIALS, [
                'key' => $key,
            ]]);

        $sc->expects($this->once())->method('encrypt')->with($rawSecret)->willReturn($encryptedSecret);
        $sc->expects($this->once())->method('writeToCacheWithRetry')->with($key, $encryptedSecret, $ttlInSeconds)->willThrowException(new Exception("mock exception"));


        // Write Exception should be traced
        Trace::shouldReceive('traceException')
            ->withArgs([Mockery::any(), Logger::ERROR, TraceCode::SIGNER_CACHE_CREATE_CREDENTIALS_FAILED, [
                'key'   => $key,
            ]])
            ->once();

        // Request duration metric with success=false should be emitted
        Trace::shouldReceive('histogram')
            ->withArgs([Metric::SIGNER_CACHE_REQUEST_DURATION_SECONDS, Mockery::any(), [
                Metric::LABEL_STATUS => false,
                Metric::LABEL_ATTEMPTS  => 3
            ]])
            ->once();

        $this->expectException(Exception::class);
        $sc->writeCredentials($key, $rawSecret, $ttlInSeconds);
    }

    /**
     * @Test
     * testWriteCredentialsFailsFailsWithNegativeTTL validates that an exception is thrown when a negative TTL is provided
     * Also verifies if appropriate logs are traced
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws Exception
     */
    public function testWriteCredentialsFailsWithNegativeTTL(): void
    {
        $key = "rzp_test_oauth_PZgJKbhpb9jbXZ";
        $encryptedSecret = "encryptedSecret";
        $ttlInSeconds = -60;

        Trace::shouldReceive('info')
            ->once()
            ->withArgs([TraceCode::SIGNER_CACHE_CREATE_CREDENTIALS, [
                'key' => $key,
            ]]);

        // Exception should be traced
        Trace::shouldReceive('traceException')
            ->withArgs([Mockery::any(), Logger::ERROR, TraceCode::SIGNER_CACHE_INVALID_TTL_ERROR, [
                'ttl'   => $ttlInSeconds,
            ]])
            ->once();

        $this->expectException(InvalidArgumentException::class);

        $sc = new SignerCache();
        $sc->writeCredentials($key, $encryptedSecret, $ttlInSeconds);
    }

    /**
     * @Test
     * testWriteToCacheWithRetry validates that a set call is made to redis with the correct params
     * Also verifies if metrics are emitted
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws Exception
     */
    public function testWriteToCacheWithRetry(): void
    {
        $key = "rzp_test_oauth_PZgJKbhpb9jbXZ";
        $cacheKey = self::CACHE_KEY_PREFIX . ':' . self::CACHE_KEY_VERSION . ':' . $key;
        $encryptedSecret = "encryptedSecret";
        $ttlInSeconds = 60;
        $redis = $this->getMockBuilder(PredisConnection::class)->setConstructorArgs([null])->addMethods(['set'])->getMock();

        // Set call should be made
        $redis->expects($this->once())->method('set')->with($cacheKey, $encryptedSecret, 'EX', $ttlInSeconds);

        // Write latency histogram metric should be emitted
        Trace::shouldReceive('histogram')
            ->withArgs([Metric::SIGNER_CACHE_WRITE_LATENCY_SECONDS, Mockery::any()])->once();

        $writeToCacheWithRetryMethod = self::getMethod('writeToCacheWithRetry');
        $sc = new SignerCache($redis);
        $numRetries = $writeToCacheWithRetryMethod->invokeArgs($sc, [$key, $encryptedSecret, $ttlInSeconds]);
        self::assertEquals(1, $numRetries);
    }

    /**
     * @Test
     * testWriteToCacheWithRetryFails that an exception is thrown when write function fails
     * Also verifies if appropriate logs and metrics are emitted
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws Exception
     */
    public function testWriteToCacheWithRetryFails(): void
    {
        $key = "rzp_test_oauth_PZgJKbhpb9jbXZ";
        $cacheKey = self::CACHE_KEY_PREFIX . ':' . self::CACHE_KEY_VERSION . ':' . $key;
        $encryptedSecret = "encryptedSecret";
        $ttlInSeconds = 60;
        $redis = $this->getMockBuilder(PredisConnection::class)->setConstructorArgs([null])->addMethods(['set'])->getMock();

        // Set calls should be made
        $redis->expects($this->exactly(3))->method('set')->with($cacheKey, $encryptedSecret, 'EX', $ttlInSeconds)->willThrowException(new ServerException("mock error"));

        // Exception should be traced
        Trace::shouldReceive('traceException')
            ->withArgs([Mockery::any(), Logger::ERROR, TraceCode::SIGNER_CACHE_REDIS_ERROR, [
                'key'   => $key,
            ]])
            ->times(3);

        $writeToCacheWithRetryMethod = self::getMethod('writeToCacheWithRetry');
        $sc = new SignerCache($redis);

        try {
            $writeToCacheWithRetryMethod->invokeArgs($sc, [$key, $encryptedSecret, $ttlInSeconds]);
            assert(false);
        }
        catch (Exception $ex)
        {
            self::assertEquals("mock error", $ex->getMessage());
        }
    }

    /**
     * @Test
     * testEncrypt validates that encrypt() returns the correct encrypted value
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     * @throws Exception
     */
    public function testEncrypt(): void
    {
        $rawValue = "thisissupersecret";
        $encryptMethod = self::getMethod('encrypt');
        $sc = new SignerCache();
        $encryptedValue = $encryptMethod->invokeArgs($sc, [$rawValue]);

        $decryptedValue = $this->decrypt(hex2bin($encryptedValue));
        self::assertEquals($rawValue, $decryptedValue);
    }


    /**
     * Decrypts value using AES-256-GCM cipher
        Ref: https://github.com/razorpay/api/blob/9a683b1dbd477282f6b4eae1b38247ae79e52de8/app/Services/CredcaseSigner.php#L235-L235
    */
    public function decrypt(string $ciphertextAndNonce) : string
    {
        $algorithmName = 'aes-256-gcm';
        $algorithmNonceSize = 12;
        $algorithmTagSize = 16;
        $key = env('SIGNER_CACHE_PRIVATE_KEY');

        $nonce = substr($ciphertextAndNonce, 0, $algorithmNonceSize);
        $ciphertext = substr($ciphertextAndNonce, $algorithmNonceSize, strlen($ciphertextAndNonce) - $algorithmNonceSize - $algorithmTagSize);
        $tag = substr($ciphertextAndNonce, strlen($ciphertextAndNonce) - $algorithmTagSize);

        return openssl_decrypt($ciphertext, $algorithmName, $key, OPENSSL_RAW_DATA, $nonce, $tag);
    }

    protected static function getMethod($name) {
        $class = new ReflectionClass('App\Services\SignerCache');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
