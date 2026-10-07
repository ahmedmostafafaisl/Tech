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

    /** Sends the notification and returns DY365's reply (null when DyService swallowed an error). */
    public function notify(object $link, string $status): mixed
    {
        return $this->dy->dyPaymentStatus([
            '_contract' => [
                'PaymentLinkId' => $link->payment_reference_id,
                'PaymentStatus' => $status,
                'ReferenceId'   => $link->dy_reference_id,
            ],
        ]);
    }


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

            $reply = $this->notify($locked, $dyStatus);

            if (! DyAcknowledgement::accepted($reply)) {
                // DyService::sendRequest() swallows HTTP/transport failures and returns null (already logged
                // separately, to storage/logs/dyservice/dyPaymentStatus.log); a non-null reply here is DY365's
                // own 200-OK business rejection (Status:false / Error set), which until now was logged nowhere
                // at all. Both cases are captured here, in the main log, every time.
                // Log::error('DY365 refused a payment-link status notification', [
                //     'dy_reference_id'      => $locked->dy_reference_id,
                //     'payment_reference_id' => $locked->payment_reference_id,
                //     'requested_status'     => $dyStatus,
                //     'current_local_status' => $locked->status,
                //     'dy_reply'             => $reply,
                // ]);

                throw new \RuntimeException("DY365 did not accept the {$dyStatus} notification");
            }

            $locked->update($update);

            return 'done';
        });
    }
}
