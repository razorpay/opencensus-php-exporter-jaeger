<?php

namespace App\Console\Commands;

use App\Constants\TraceCode;
use Razorpay\Trace\Facades\Trace;

trait RateLimitTrait
{
    private int $rateLimitCounter;
    private int $rateLimitThresholdPerSec;
    private float $rateLimitTimeStart;
    private int $rateLimitBackoffIntervalSecs;

    protected function rateLimitIfRequired(): void
    {
        $this->rateLimitCounter++;

        if ($this->rateLimitCounter > $this->rateLimitThresholdPerSec) {
            if (microtime(true) - $this->rateLimitTimeStart < 1) {
                Trace::info(TraceCode::RATE_LIMIT, ['message' => 'Rate limit applied. Sleeping for ' . $this->rateLimitBackoffIntervalSecs . ' secs']);
                sleep($this->rateLimitBackoffIntervalSecs);
            }

            $this->rateLimitCounter = 0;
            $this->rateLimitTimeStart = microtime(true);
        }
    }
}
