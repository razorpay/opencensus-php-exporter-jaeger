<?php

namespace App\Services;

use App\Constants\TraceCode;
use App\Exception\InvalidArgumentException;
use App\Exception\RuntimeException;
use Exception;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Redis\Connectors\PredisConnector;
use App\Constants\Metric;
use Predis\PredisException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Razorpay\Trace\Logger as Trace;

class SignerCache
{
    const CACHE_KEY_PREFIX = "credcase:ks";
    const CACHE_KEY_VERSION = "v1";

    const ENCRYPTION_ALGORITHM_NAME = 'aes-256-gcm';
    const ENCRYPTION_NONCE_SIZE = 12;
    const ENCRYPTION_TAG_LENGTH = 16;

    protected Connection $redis;
    protected int $maxRedisAttempts;
    protected string $privateKey;
    protected string $prefix;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(Connection $redis = null)
    {
        $config = app('config')->get('database.redis')['signer_cache'];
        $this->redis = $redis ?? (new PredisConnector)->connect($config, []);

        $this->maxRedisAttempts = intval(env('SIGNER_CACHE_MAX_ATTEMPTS'));
        if ($this->maxRedisAttempts == 0)
            $this->maxRedisAttempts = 3;

        $this->privateKey = env('SIGNER_CACHE_PRIVATE_KEY');
        $this->prefix = self::CACHE_KEY_PREFIX . ':' . self::CACHE_KEY_VERSION . ':';
    }

    /**
     * Encrypts the raw secret provided and calls the function that writes credentials to the cache
     * @param string $key
     * @param string $rawSecret
     * @param int $ttlInSeconds
     * @throws Exception
     */
    public function writeCredentials(string $key, string $rawSecret, int $ttlInSeconds = 0): void
    {
        $start = microtime(true);
        $success = false;
        $numAttemptsTaken = $this->maxRedisAttempts;

        app('trace')->info(TraceCode::SIGNER_CACHE_CREATE_CREDENTIALS, [
                'key'  => $key,
            ]);

        if ($ttlInSeconds < 0) {
            // TTL specified is negative, return an exception
            $ex = new InvalidArgumentException("TTL provided is negative");
            app('trace')->traceException($ex, Trace::ERROR, TraceCode::SIGNER_CACHE_INVALID_TTL_ERROR, ['ttl' => $ttlInSeconds]);
            throw $ex;
        }

        try
        {
            $encryptedSecret = $this->encrypt($rawSecret);
            $numAttemptsTaken = $this->writeToCacheWithRetry($key, $encryptedSecret, $ttlInSeconds);
            $success = true;
        }
        catch (Exception $ex)
        {
            app('trace')->traceException($ex, Trace::ERROR, TraceCode::SIGNER_CACHE_CREATE_CREDENTIALS_FAILED, [
                'key'   => $key,
            ]);
            throw $ex;
        }
        finally
        {
            $durationInSec = microtime(true) - $start;
            app('trace')->histogram(Metric::SIGNER_CACHE_REQUEST_DURATION_SECONDS, $durationInSec, [
                Metric::LABEL_STATUS => $success,
                Metric::LABEL_ATTEMPTS  => $numAttemptsTaken
            ]);
        }
    }

    /**
     * Set (key=key, value=secret) in signer redis cache with an optional ttl
     * Retries the SET call for a configured number of attempts
     * Either throws an exception in case all retries were exhausted or returns the number of attempts required
     * Note, Retries are done only for Redis-related errors
     * @throws Exception
     */
    protected function writeToCacheWithRetry(string $key, string $encryptedSecret, int $ttlInSeconds = 0): int
    {
        $numAttempts = 0;
        $cacheKey = $this->prefix . $key;
        while (true) {
            try {
                $numAttempts++;
                $redisWriteStartedAt = microtime(true);
                if ($ttlInSeconds > 0)
                    $this->redis->set($cacheKey, $encryptedSecret, 'EX', $ttlInSeconds);
                else
                    $this->redis->set($cacheKey, $encryptedSecret);

                app('trace')->histogram(Metric::SIGNER_CACHE_WRITE_LATENCY_SECONDS, microtime(true) - $redisWriteStartedAt);

                return $numAttempts;
            }
            catch (PredisException $ex) {
                app('trace')->traceException($ex, Trace::ERROR, TraceCode::SIGNER_CACHE_REDIS_ERROR, ['key' => $key]);

                if ($numAttempts >= $this->maxRedisAttempts)
                    throw $ex;
            }
        }
    }

    /**
     * Encrypts data using 256-bit AES-GCM. It corresponds to the encryption logic used in signer.
     * Ref: https://github.com/razorpay/outbox-php/blob/c54ba3be97d8f4fb967f606b9c67948bf2c96a14/src/Encrypt/AES256GCMEncrypt.php#L27
     * @param string $data
     * @return string
     * @throws Exception
     */
    protected function encrypt(string $data): string
    {
        $nonce = openssl_random_pseudo_bytes(self::ENCRYPTION_NONCE_SIZE);
        $tag = ""; // will be filled by openssl_encrypt
        $cipherText =  openssl_encrypt($data, self::ENCRYPTION_ALGORITHM_NAME, $this->privateKey, OPENSSL_RAW_DATA, $nonce, $tag, "", self::ENCRYPTION_TAG_LENGTH);

        if ($cipherText === false)
        {
            throw new RuntimeException('Could not encrypt secret');
        }

        return bin2hex($nonce . $cipherText . $tag);
    }
}
