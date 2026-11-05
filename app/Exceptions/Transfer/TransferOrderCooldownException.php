<?php

namespace App\Exceptions\Transfer;

use Exception;

/**
 * Thrown when a technician tries to create a transfer order while still on cooldown from their previous
 * one — renders 429 (Too Many Requests), the correct status for a rate-limit/cooldown rejection, distinct
 * from TransferValidationException's 422 for genuinely invalid input. remaining_seconds is included so the
 * client doesn't need a separate call to find out how long to wait — the same value
 * getTransferOrders() surfaces once the cooldown is already running.
 */
class TransferOrderCooldownException extends Exception
{
    public function __construct(private readonly int $remainingSeconds)
    {
        parent::__construct('You must wait before creating another transfer order.');
    }

    public function render($request)
    {
        return response()->json([
            'success'           => false,
            'message'           => $this->getMessage(),
            'remaining_seconds' => $this->remainingSeconds,
        ], 429);
    }
}
