<?php

namespace App\Services\Payment;

use App\Models\DyPaymentLink;
use App\Services\DY365\DyAcknowledgement;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The one place a DY payment link changes state and DY365 is told about it (extracted unchanged from DyController,
 * so the redirect handlers and the Tamara webhook share a single implementation).
 */
final class DyPaymentLinkTransitions
{
    /** A link in one of these states can still become success / cancelled / failed. */
    public const OPEN_STATES = ['created', 'pending', 'failed', 'cancelled'];

    public function __construct(private readonly DyService $dy) {}

    /**
     * Sends the notification and returns DY365's reply (null when DyService swallowed an error). The body
     * sent and DY365's reply are saved on the link itself (dy_body/dy_response) every time this runs,
     * regardless of whether DY365 goes on to accept or reject it — that's still a record worth having.
     *
     * PaymentLinkId/ReferenceId were previously swapped here: dy_reference_id is DY365's own identifier for
     * this link (it's the column with the unique constraint, and every gateway integration — Tabby, Tamara,
     * ClickPay — sets it from DY365's own reference_id when the link is created), so it belongs in
     * PaymentLinkId. payment_reference_id is the payment gateway's own transaction id, which belongs in
     * ReferenceId.
     */
    public function notify(object $link, string $status): mixed
    {
        $body = [
            '_contract' => [
                'PaymentLinkId'   => $link->dy_reference_id,
                'PaymentStatus'   => $status,
                'ReferenceId'     => $link->payment_reference_id,
                'customeraccount' => $link->customer_account,
                'bookId'          => $link->book_id,
                'Paymode'         => $link->payment_method,
                'PaimentAmount'   => $link->amount,
            ],
        ];

        $reply = $this->dy->dyPaymentStatus($body);

        $link->update([
            'dy_body'     => json_encode($body, JSON_UNESCAPED_UNICODE),
            'dy_response' => json_encode($reply, JSON_UNESCAPED_UNICODE),
        ]);

        return $reply;
    }


    /**
     * @return string 'already' | 'invalid_state' | 'done' | 'done_dy_notification_failed'
     *
     * The local update is applied BEFORE notifying DY365, not after. The provider (Tabby/Tamara/ClickPay)
     * already confirmed this payment before complete() is ever called — that confirmation, not DY365's own
     * acknowledgement, is the source of truth for whether the customer paid. Previously the local update
     * only happened once DY365 accepted the notification, and a DY365 outage or rejection left a genuinely
     * paid payment stuck showing its old status (e.g. "created") indefinitely, with the customer shown a
     * generic error despite having actually paid.
     *
     * DY365 not acknowledging the notification is now reported through the return value
     * ('done_dy_notification_failed') instead of an exception — every caller treats it the same as 'done'
     * (the payment is genuinely paid and recorded locally), differing only in that this is also logged here
     * for operational follow-up, and dy_body/dy_response (see notify()) carry what was sent and what DY365
     * replied, for a retry or manual reconciliation.
     */
    public function complete(DyPaymentLink $link, array $update, string $dyStatus): string
    {
        return DB::transaction(function () use ($link, $update, $dyStatus) {
            $locked = DyPaymentLink::whereKey($link->id)->lockForUpdate()->first();

            if ($locked->status === $update['status']) {
                return 'already';
            }

            if (! in_array($locked->status, self::OPEN_STATES, true)) {
                return 'invalid_state';
            }

            $locked->update($update);

            $reply = $this->notify($locked, $dyStatus);

            if (! DyAcknowledgement::accepted($reply)) {
                // DyService::sendRequest() swallows HTTP/transport failures and returns null (already logged
                // separately, to storage/logs/dyservice/dyPaymentStatus.log); a non-null reply here is DY365's
                // own 200-OK business rejection (Status:false / Error set), which until now was logged nowhere
                // at all. Both cases are captured here, in the main log, every time.
                Log::error('DY365 refused a payment-link status notification; local record updated regardless', [
                    'dy_reference_id'      => $locked->dy_reference_id,
                    'payment_reference_id' => $locked->payment_reference_id,
                    'requested_status'     => $dyStatus,
                    'current_local_status' => $locked->status,
                    'dy_reply'             => $reply,
                ]);

                return 'done_dy_notification_failed';
            }

            return 'done';
        });
    }
}
