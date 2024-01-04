<?php

namespace App\Console\Commands;

use App\Exception\LogicException;
use App\Exception\NotFoundException;
use App\Services\EdgeService;
use Illuminate\Console\Command;
use Razorpay\OAuth\Token\Entity;
use Razorpay\Trace\Facades\Trace;
use App\Constants\TraceCode;
use Razorpay\OAuth\Token\Repository as TokenRepo;


// DISCLAIMER: This will override the existing tags in Edge's identifiers table, with the tags specified in PATCH payload

class PublicTokenPatch extends Command
{
    use RateLimitTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'public_token:patch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Patch Public Tokens at Edge to add client ID and fix scopes';

    private EdgeService $edge;
    private TokenRepo $tokenRepo;
    private int $queryBatchSize;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        // Max number of requests that will be made to Edge per second
        $this->rateLimitThresholdPerSec = intval(getenv("PUBLIC_TOKEN_PATCH_MAX_REQUESTS_PER_SEC"));

        // Number of seconds to wait if threshold is exceeded
        $this->rateLimitBackoffIntervalSecs = intval(getenv("PUBLIC_TOKEN_PATCH_BACKOFF_INTERVAL_SECS"));

        // Max number of tokens to read in each SELECT query
        $this->queryBatchSize = intval(getenv("PUBLIC_TOKEN_PATCH_QUERY_BATCH_SIZE"));

        if ($this->rateLimitThresholdPerSec === 0)
            $this->rateLimitThresholdPerSec = 50;

        if ($this->rateLimitBackoffIntervalSecs === 0)
            $this->rateLimitBackoffIntervalSecs = 10;

        if ($this->queryBatchSize === 0)
            $this->queryBatchSize = 10000;


        $this->tokenRepo = new TokenRepo();
        $this->edge = new EdgeService(null, env('EDGE_URL'), env('EDGE_SECRET'));
        $this->rateLimitCounter = 0;
    }

    /**
     * Read all existing public token entities and create patch requests for the same.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        Trace::info(TraceCode::PATCH_PUBLIC_TOKEN_INPUT_PARAMS, [
            'rate_limit_threshold_per_sec' => $this->rateLimitThresholdPerSec,
            'rate_limit_backoff_interval_secs' => $this->rateLimitBackoffIntervalSecs,
            'query_batch_size' => $this->queryBatchSize
        ]);

        $count = $this->queryBatchSize;
        $skip = 0;
        $numSuccess = $numFail = $numNotFound = 0;
        $this->rateLimitTimeStart = microtime(true);

        while ($count === $this->queryBatchSize) {
            $tokens = $this->tokenRepo->fetchAllActivePublicTokens($this->queryBatchSize, $skip);
            $count = $tokens->count();
            $skip += $count;

            foreach ($tokens as $token) {
                $publicToken = $token->getPublicTokenWithPrefix();
                $clientId = $token->getAttribute(Entity::CLIENT_ID);
                $mode = $token->getMode();
                $scopes = $token->getScopes() ?? [];

                try {
                    $this->edge->patchIdentifier($publicToken, [
                        'tags' => $this->edge->getTags($scopes, $mode),
                        'ref_id' => $clientId
                    ]);

                    Trace::info(TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_SUCCESS, ['public_token' => $publicToken]);
                    $numSuccess++;
                } catch (NotFoundException) {
                    // Not expected to happen as tokens in auth service and Edge should always be in sync
                    Trace::warn(TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_NOT_FOUND, ['public_token' => $publicToken]);
                    $numNotFound++;
                } catch (LogicException $ex) {
                    Trace::error(TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_FAILED, ['public_token' => $publicToken, 'exception' => $ex->getMessage()]);
                    $numFail++;
                }

                $this->rateLimitIfRequired();
            }

            Trace::info(TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_NUM_RECORDS, [
                'total' => $skip,
                'success' => $numSuccess,
                'fail' => $numFail,
                'not_found' => $numNotFound]);
        }
        return null;
    }
}
