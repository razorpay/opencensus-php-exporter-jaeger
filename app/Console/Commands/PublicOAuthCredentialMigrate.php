<?php

namespace App\Console\Commands;
use App\Constants\TraceCode;
use App\Services\SignerCache;
use Illuminate\Console\Command;
use League\OAuth2\Server\CryptTrait;
use Razorpay\OAuth\Token\Repository;
use Razorpay\Trace\Facades\Trace;

class PublicOAuthCredentialMigrate extends Command
{
    use CryptTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'public_oauth_credential:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate active Public OAuth Credentials to Signer Cache';

    private Repository $tokenRepo;
    private SignerCache $signerCache;
    private int $queryBatchSize;

    private int $rateLimitCounter;
    private int $rateLimitThresholdPerSec;
    private float $rateLimitTimeStart;
    private int $rateLimitBackoffIntervalSecs;

    /**
     * Create a new command instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->rateLimitThresholdPerSec = intval(getenv("PUBLIC_OAUTH_CREDENTIAL_MIGRATE_MAX_REQUESTS_PER_SEC"));
        $this->rateLimitBackoffIntervalSecs = intval(getenv("PUBLIC_OAUTH_CREDENTIAL_MIGRATE_BACKOFF_INTERVAL_SECS"));
        $this->queryBatchSize = intval(getenv("PUBLIC_OAUTH_QUERY_BATCH_SIZE"));

        if ($this->rateLimitThresholdPerSec === 0)
            $this->rateLimitThresholdPerSec = 50;

        if ($this->rateLimitBackoffIntervalSecs === 0)
            $this->rateLimitBackoffIntervalSecs = 10;

        if ($this->queryBatchSize === 0)
            $this->queryBatchSize = 10000;


        $this->signerCache = new SignerCache();
        $this->tokenRepo = new Repository();
        $this->rateLimitCounter = 0;

        $this->encryptionKey = env('OAUTH_ENCRYPTION_KEY'); // used to decrypt client secrets fetched from DB
        if (empty(trim($this->encryptionKey)))
            throw new \Exception("OAuth Encryption Key is not provided");
    }

    /**
     * Fetch all active public OAuth credentials from DB and write them to Signer Cache
     * @return mixed
     */
    public function handle(): mixed
    {
        Trace::info(TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_INPUT_PARAMS, [
            'rate_limit_threshold_per_sec' => $this->rateLimitThresholdPerSec,
            'rate_limit_backoff_interval_secs' => $this->rateLimitBackoffIntervalSecs,
            'query_batch_size' => $this->queryBatchSize
            ]);

        $count = $this->queryBatchSize;
        $skip = 0;
        $numSuccess = $numFail = 0;
        $this->rateLimitTimeStart = microtime(true);

        while ($count === $this->queryBatchSize)
        {
            $credentials = $this->tokenRepo->fetchAllActivePublicOAuthCredentials($this->queryBatchSize, $skip);
            $count = $credentials->count();
            $skip += $count;

            foreach ($credentials as $credential) {
                $publicToken = $credential->getPublicTokenWithPrefix();
                $ttlInSeconds = $credential->getExpiryDateTime()->getTimestamp() - (new \DateTime('now'))->getTimestamp();
                $rawSecret = $this->decrypt($credential->secret);

                Trace::info(TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST, ['public_token' => $publicToken]);

                try {
                    $this->signerCache->writeCredentials($publicToken, $rawSecret, $ttlInSeconds);
                    Trace::info(TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_SUCCESS, ['public_token' => $publicToken]);
                    $numSuccess++;
                }
                catch (\Exception $ex)
                {
                    Trace::error(TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_FAILURE, ['public_token' => $publicToken, 'exception' => $ex->getMessage()]);
                    $numFail++;
                }

                $this->rateLimitIfRequired();
            }
        }

        Trace::info(TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_NUM_RECORDS, [
            'total' => $skip,
            'success' => $numSuccess,
            'fail' => $numFail]);

        return null;
    }


    /**
     * Checks if number of requests made in the previous second are less than threshold
     * If more requests were made, it pauses the execution for the number of seconds specified in backoff interval
     *
     * @return void
     */
    protected function rateLimitIfRequired(): void
    {
        $this->rateLimitCounter++;

        if ($this->rateLimitCounter > $this->rateLimitThresholdPerSec)
        {
            if (microtime(true) - $this->rateLimitTimeStart < 1)
            {
                Trace::info(TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_RATE_LIMIT, ['message' => 'Rate limit applied. Sleeping for ' . $this->rateLimitBackoffIntervalSecs . ' secs']);
                sleep($this->rateLimitBackoffIntervalSecs);
            }


            $this->rateLimitCounter = 0;
            $this->rateLimitTimeStart = microtime(true);
        }
    }
}
