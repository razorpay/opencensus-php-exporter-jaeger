<?php

namespace App\Tests\Unit\Console\Commands;

use App\Console\Commands\PublicOAuthCredentialMigrate;
use App\Console\Commands\PublicTokenPatch;
use App\Constants\TraceCode;
use App\Exception\LogicException;
use App\Exception\NotFoundException;
use App\Services\EdgeService;
use App\Tests\Unit\UnitTestCase;
use Predis\ClientException;
use Razorpay\OAuth\Token\Entity;
use Razorpay\Trace\Facades\Trace;

class PublicTokenPatchTest extends UnitTestCase
{
    /**
     * @Test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * testHandle validates that appropriate logs are printed
     * when 3 public tokens are fetched from DB in a paginated fashion and patch requests are sent
     * and one of the writes throws an error
     * @return void
     */
    public function testHandle() {
        $queryBatchSize = 2;
        putenv("PUBLIC_TOKEN_PATCH_QUERY_BATCH_SIZE=" . $queryBatchSize);

        Trace::shouldReceive('info')
            ->withArgs([TraceCode::PATCH_PUBLIC_TOKEN_INPUT_PARAMS, [
                'rate_limit_threshold_per_sec' => 50, // default value
                'rate_limit_backoff_interval_secs' => 10,  // default value
                'query_batch_size' => 2]]);

        $token1 = $this->getTokenWithAttributes("1ZgJKbhpb9jbXZ", "test", "1ZgJKbhclient1", ["read_only"]);
        $token2 = $this->getTokenWithAttributes("2ZgJKbhpb9jbXZ", "live", "2ZgJKbhclient2", ["read_only", "rx_read_only"]);
        $token3 = $this->getTokenWithAttributes("3ZgJKbhpb9jbXZ", "test", "3ZgJKbhclient3", ["read_write"]);

        // Paginated calls
        $tokenRepo = \Mockery::mock('overload:Razorpay\OAuth\Token\Repository');
        $tokenRepo->shouldReceive('fetchAllActivePublicTokens')->withArgs([$queryBatchSize, 0])->andReturn(collect([$token1, $token2]))->once();
        $tokenRepo->shouldReceive('fetchAllActivePublicTokens')->withArgs([$queryBatchSize, 2])->andReturn(collect([$token3]))->once();


        $edge = \Mockery::mock('overload:App\Services\EdgeService');

        $token1Tags = ['r~read_only', 'm~t'];
        $token2Tags = ['r~read_only', 'r~rx_read_only', 'm~l'];
        $token3Tags = ['r~read_write', 'm~t'];
        $edge->shouldReceive('getTags')->withArgs([$token1->getScopes(), $token1->getMode()])->andReturn($token1Tags);
        $edge->shouldReceive('getTags')->withArgs([$token2->getScopes(), $token2->getMode()])->andReturn($token2Tags);
        $edge->shouldReceive('getTags')->withArgs([$token3->getScopes(), $token3->getMode()])->andReturn($token3Tags);


        // token1 - successful request
        // token2 - not found
        // token3 - request failed
        $edge->shouldReceive('patchIdentifier')->withArgs([$token1->getPublicTokenWithPrefix(), ['tags' => $token1Tags, 'ref_id' => $token1->getAttribute("client_id")]])->once();
        $edge->shouldReceive('patchIdentifier')->withArgs([$token2->getPublicTokenWithPrefix(), ['tags' => $token2Tags, 'ref_id' => $token2->getAttribute("client_id")]])->andThrow(new NotFoundException("mock error"))->once();
        $edge->shouldReceive('patchIdentifier')->withArgs([$token3->getPublicTokenWithPrefix(), ['tags' => $token3Tags, 'ref_id' => $token3->getAttribute("client_id")]])->andThrow(new LogicException("mock error"));

        Trace::shouldReceive('info')->withArgs([TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_SUCCESS, ['public_token' => $token1->getPublicTokenWithPrefix()]])->once();
        Trace::shouldReceive('warn')->withArgs([TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_NOT_FOUND, ['public_token' => $token2->getPublicTokenWithPrefix()]])->once();
        Trace::shouldReceive('error')->withArgs([TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_FAILED, ['public_token' => $token3->getPublicTokenWithPrefix(), 'exception' => 'mock error']])->once();

        // after 1st batch
        Trace::shouldReceive('info')->withArgs([TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_NUM_RECORDS, ['total' => 2, 'success' => 1, 'fail' => 0, 'not_found' => 1]])->once();
        // after 2nd batch
        Trace::shouldReceive('info')->withArgs([TraceCode::PATCH_PUBLIC_TOKEN_REQUEST_NUM_RECORDS, ['total' => 3, 'success' => 1, 'fail' => 1, 'not_found' => 1]])->once();

        $command = new PublicTokenPatch();
        $command->handle();
    }


    protected function getTokenWithAttributes($publicToken, $mode, $clientId, $scopes): Entity
    {
        $token = new Entity();
        $token->setAttribute("public_token", $publicToken);
        $token->setAttribute("mode", $mode);
        $token->setAttribute("client_id", $clientId);
        $token->setAttribute("scopes", $scopes);
        return $token;
    }



}
