<?php

namespace App\Services\Payment;

use App\Models\DyPaymentLink;
use App\Services\DY365\DyAcknowledgement;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\DB;

/**
 * The one place a DY payment link changes state and DY365 is told about it (extracted unchanged from DyController,
 * so the redirect handlers and the Tamara webhook share a single implementation).
 */
final class DyPaymentLinkTransitions
{
    /** A link in one of these states can still become success / cancelled / failed. */
    public const OPEN_STATES = ['created', 'pending', 'failed', 'cancelled'];

    public function __construct(private readonly DyService $dy)
    {
    }

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

    /**
     * Moves an open link to its new status and tells DY365 — once. The row is locked, so a repeated or concurrent
     * request cannot notify twice. If DY365 does not accept the notification nothing is changed (the transaction
     * rolls back) and the call can simply be repeated.
     *
     * @return string 'done' | 'already' | 'invalid_state'
     *
     * @throws \RuntimeException when DY365 did not accept the notification
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

            if (! DyAcknowledgement::accepted($this->notify($locked, $dyStatus))) {
                throw new \RuntimeException("DY365 did not accept the {$dyStatus} notification");
            }

            $locked->update($update);

            return 'done';
        });
    }
}
