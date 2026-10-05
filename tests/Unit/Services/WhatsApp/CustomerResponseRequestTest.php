<?php

namespace Tests\Unit\Services\WhatsApp;

use App\Services\DY365\DyTransportOutcome as O;
use App\Services\WhatsApp\CustomerResponseRequest as R;
use PHPUnit\Framework\TestCase;

class CustomerResponseRequestTest extends TestCase
{
    /** @test */
    public function each_reply_maps_to_the_right_request_type(): void
    {
        $this->assertSame(2, R::body('APP1', 'SO-1', 'confirm')['_contract']['requestType']);
        $this->assertSame(0, R::body('APP1', 'SO-1', 'cancel')['_contract']['requestType']);
        $this->assertSame(1, R::body('APP1', 'SO-1', 'reschedule')['_contract']['requestType']);
    }

    /** @test */
    public function the_body_has_exactly_the_contract_dy_expects(): void
    {
        $this->assertSame(
            ['_contract' => ['bookId' => 'APP1', 'salesOrderId' => 'SO-1', 'actionOwner' => 2, 'requestType' => 1]],
            R::body('APP1', 'SO-1', 'reschedule')
        );
    }

    /** @test */
    public function pending_and_unknown_replies_are_never_sent(): void
    {
        foreach (['pending', 'maybe', ''] as $reply) {
            try {
                R::body('APP1', 'SO-1', $reply);
                $this->assertTrue(false, "'{$reply}' should have been refused");
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }
    }

    /** @test */
    public function dy_answering_status_true_is_flag_one(): void
    {
        $r = R::record(O::answered(200, ['Status' => true, 'Code' => 200]));

        $this->assertSame(1, $r['flag']);
        $this->assertSame('answered', $r['dy_response']['outcome']);
        $this->assertSame(['Status' => true, 'Code' => 200], $r['dy_response']['data']);
    }

    /** @test */
    public function dy_answering_status_false_is_flag_zero_but_keeps_the_answer(): void
    {
        $r = R::record(O::answered(200, ['Status' => false, 'Error' => 'Already cancelled']));

        $this->assertSame(0, $r['flag']);
        $this->assertSame(['Status' => false, 'Error' => 'Already cancelled'], $r['dy_response']['data']);
    }

    /** @test */
    public function an_http_refusal_is_flag_zero_with_the_error(): void
    {
        $withBody = R::record(O::answered(422, ['Error' => 'bad']));
        $noBody   = R::record(O::answered(400, null));

        $this->assertSame(0, $withBody['flag']);
        $this->assertSame(['Error' => 'bad'], $withBody['dy_response']['error']);
        $this->assertSame(['message' => 'Dynamics replied with HTTP 400'], $noBody['dy_response']['error']);
    }

    /** @test */
    public function unconfirmed_and_not_sent_stay_unresolved_but_say_which_they_are(): void
    {
        $unconfirmed = R::record(O::unconfirmed('cURL error 28: Operation timed out'));
        $notSent     = R::record(O::notSent('cURL error 7: Connection refused'));

        $this->assertSame(0, $unconfirmed['flag']);
        $this->assertSame('unconfirmed', $unconfirmed['dy_response']['outcome']);
        $this->assertSame(0, $notSent['flag']);
        $this->assertSame('not_sent', $notSent['dy_response']['outcome']);
        $this->assertSame(['message' => 'cURL error 7: Connection refused'], $notSent['dy_response']['error']);
    }

    /** @test */
    public function the_stored_response_is_always_json_encodable(): void
    {
        foreach ([O::answered(200, ['Status' => true]), O::unconfirmed('x'), O::notSent('y'), O::answered(500, null)] as $o) {
            $this->assertTrue(is_string(json_encode(R::record($o)['dy_response'], JSON_UNESCAPED_UNICODE)));
        }
    }
}
