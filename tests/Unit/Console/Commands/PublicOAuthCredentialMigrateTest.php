<?php

namespace App\Tests\Unit\Console\Commands;

use App\Console\Commands\PublicOAuthCredentialMigrate;
use App\Constants\TraceCode;
use App\Tests\Unit\UnitTestCase;
use Predis\ClientException;
use Razorpay\OAuth\Token\Entity;
use Razorpay\Trace\Facades\Trace;

class PublicOAuthCredentialMigrateTest extends UnitTestCase
{
    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandle validates that appropriate logs are printed
     * when 3 public OAuth credentials from DB in a paginated fashion and are written to Signer Cache
     * and one of the writes throws an error
     * @return void
     */
    public function testHandle() {
        $queryBatchSize = 2;
        putenv("PUBLIC_OAUTH_QUERY_BATCH_SIZE=" . $queryBatchSize);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_INPUT_PARAMS, [
                'rate_limit_threshold_per_sec' => 50, // default value
                'rate_limit_backoff_interval_secs' => 10,  // default value
                'query_batch_size' => 2]]);

        $token1 = $this->getTokenWithAttributes("1ZgJKbhpb9jbXZ", "test", (new \DateTime('now'))->getTimestamp(), "def5020061cd88dbf2cc750ced67d686052ea8a68ce743f91cde05449eb8ffc21d8a98d2deca51f1066b11492bb07b0dcfc09a64d9f9197cff4b51d74c5765d55053884154e9a1ce14a7c25c14213f1de1574c04fa79add4305d25deedc58a0b1afd8e89");
        $token2 = $this->getTokenWithAttributes("2ZgJKbhpb9jbXZ", "test", (new \DateTime('now'))->getTimestamp(), "def5020061cd88dbf2cc750ced67d686052ea8a68ce743f91cde05449eb8ffc21d8a98d2deca51f1066b11492bb07b0dcfc09a64d9f9197cff4b51d74c5765d55053884154e9a1ce14a7c25c14213f1de1574c04fa79add4305d25deedc58a0b1afd8e89");
        $token3 = $this->getTokenWithAttributes("3ZgJKbhpb9jbXZ", "test", (new \DateTime('now'))->getTimestamp(), "def5020061cd88dbf2cc750ced67d686052ea8a68ce743f91cde05449eb8ffc21d8a98d2deca51f1066b11492bb07b0dcfc09a64d9f9197cff4b51d74c5765d55053884154e9a1ce14a7c25c14213f1de1574c04fa79add4305d25deedc58a0b1afd8e89");

        // Paginated calls
        $tokenRepo = \Mockery::mock('overload:Razorpay\OAuth\Token\Repository');
        $tokenRepo->shouldReceive('fetchAllActivePublicOAuthCredentials')->withArgs([$queryBatchSize, 0])->andReturn(collect([$token1, $token2]))->once();
        $tokenRepo->shouldReceive('fetchAllActivePublicOAuthCredentials')->withArgs([$queryBatchSize, 2])->andReturn(collect([$token3]))->once();

        Trace::shouldReceive('info')->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST, ['public_token' => $token1->getPublicTokenWithPrefix()]])->once();
        Trace::shouldReceive('info')->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST, ['public_token' => $token2->getPublicTokenWithPrefix()]])->once();
        Trace::shouldReceive('info')->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST, ['public_token' => $token3->getPublicTokenWithPrefix()]])->once();

        // Write should succeed for 2 tokens and fail for 1 token
        $sc = \Mockery::mock('overload:App\Services\SignerCache');
        $sc->shouldReceive('writeCredentials')->withArgs([$token1->getPublicTokenWithPrefix(), \Mockery::any(), \Mockery::any()])->once();
        $sc->shouldReceive('writeCredentials')->withArgs([$token2->getPublicTokenWithPrefix(), \Mockery::any(), \Mockery::any()])->andThrow(new ClientException("mock error"))->once();
        $sc->shouldReceive('writeCredentials')->withArgs([$token3->getPublicTokenWithPrefix(), \Mockery::any(), \Mockery::any()])->once();

        Trace::shouldReceive('info')->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_SUCCESS, ['public_token' => $token1->getPublicTokenWithPrefix()]])->once();
        Trace::shouldReceive('error')->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_FAILURE, ['public_token' => $token2->getPublicTokenWithPrefix(), 'exception' => 'mock error']])->once();
        Trace::shouldReceive('info')->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_REQUEST_SUCCESS, ['public_token' => $token3->getPublicTokenWithPrefix()]])->once();

        Trace::shouldReceive('info')->withArgs([TraceCode::MIGRATE_PUBLIC_OAUTH_CREDS_NUM_RECORDS, ['total' => 3, 'success' => 2, 'fail' => 1]])->once();

        $command = new PublicOAuthCredentialMigrate();
        $command->handle();
    }


    protected function getTokenWithAttributes($publicToken, $mode, $expiresAt, $secret): Entity
    {
        $token = new Entity();
        $token->setAttribute("public_token", $publicToken);
        $token->setAttribute("mode", $mode);
        $token->setAttribute("expires_at", $expiresAt);
        $token->setAttribute("secret", $secret);
        return $token;
    }



}
