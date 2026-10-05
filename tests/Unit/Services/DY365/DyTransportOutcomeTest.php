<?php

namespace Tests\Unit\Services\DY365;

use App\Services\DY365\DyTransportOutcome;
use PHPUnit\Framework\TestCase;

class DyTransportOutcomeTest extends TestCase
{
    /** @test */
    public function a_json_reply_is_an_answer(): void
    {
        $o = DyTransportOutcome::fromResponse(200, ['Status' => true]);

        $this->assertSame(DyTransportOutcome::ANSWERED, $o['outcome']);
        $this->assertSame(200, $o['status']);
        $this->assertSame(['Status' => true], $o['body']);
    }

    /** @test */
    public function a_refusal_is_still_an_answer_even_without_a_json_body(): void
    {
        $o = DyTransportOutcome::fromResponse(400, null);

        $this->assertSame(DyTransportOutcome::ANSWERED, $o['outcome']);
        $this->assertSame(400, $o['status']);
        $this->assertNull($o['body']);
    }

    /** @test */
    public function server_errors_are_unconfirmed_because_the_effect_is_unknown(): void
    {
        foreach ([500, 502, 503, 504] as $status) {
            $this->assertSame(DyTransportOutcome::UNCONFIRMED, DyTransportOutcome::fromResponse($status, null)['outcome']);
        }
    }

    /** @test */
    public function a_read_timeout_means_it_may_have_been_received(): void
    {
        $o = DyTransportOutcome::fromTransportError(
            'cURL error 28: Operation timed out after 54001 milliseconds with 0 bytes received for https://x/api'
        );

        $this->assertSame(DyTransportOutcome::UNCONFIRMED, $o['outcome']);
    }

    /** @test */
    public function resets_and_empty_replies_after_sending_are_unconfirmed(): void
    {
        $this->assertSame(DyTransportOutcome::UNCONFIRMED, DyTransportOutcome::fromTransportError('cURL error 56: Recv failure: Connection reset by peer')['outcome']);
        $this->assertSame(DyTransportOutcome::UNCONFIRMED, DyTransportOutcome::fromTransportError('cURL error 52: Empty reply from server')['outcome']);
    }

    /** @test */
    public function connect_dns_and_tls_failures_mean_it_was_never_sent(): void
    {
        foreach (
            [
                'cURL error 28: Connection timed out after 5001 milliseconds',
                'cURL error 28: Resolving timed out after 5000 milliseconds',
                'cURL error 6: Could not resolve host: dy.example',
                'cURL error 7: Failed to connect to dy.example port 443 after 12 ms: Connection refused',
                'cURL error 60: SSL certificate problem: unable to get local issuer certificate',
            ] as $message
        ) {
            $this->assertSame(DyTransportOutcome::NOT_SENT, DyTransportOutcome::fromTransportError($message)['outcome'], $message);
        }
    }
}
