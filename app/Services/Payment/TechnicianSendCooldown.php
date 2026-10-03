<?php

namespace App\Services\Payment;

use App\Models\DirectAppointment;
use App\Models\Setting;
use Carbon\Carbon;


class TechnicianSendCooldown
{
    // Shape-only LIKE pattern: every "_" matches exactly one character.
    // Malformed markers such as "done:48:37" (which exist in this DB — see
    // InvoiceController::getInvoiceDetailsByBookId) never match it, and they
    // would otherwise sort AFTER real timestamps and be picked first.
    private const TS_SHAPE = '20__-__-__ __:__:__';

    public function cooldownSeconds(): int
    {
        return max(0, (int) Setting::get('appointment_cooldown_minutes', 15)) * 60;
    }

    /**
     * Seconds left before this technician may send another appointment to
     * DY, or null when they are free to send. $excludeBookId keeps the
     * appointment being processed from counting against itself.
     */
    public function remainingSeconds($techId, ?string $excludeBookId = null): ?int
    {
        if ($techId === null || $techId === '') {
            return null;
        }

        $cooldown = $this->cooldownSeconds();

        if ($cooldown <= 0) {
            return null;
        }

        $lastSentAt = $this->lastSentAt((string) $techId, $excludeBookId);

        if ($lastSentAt === null) {
            return null;
        }

        $remaining = $cooldown - (now()->timestamp - $lastSentAt->timestamp);

        return $remaining > 0 ? (int) $remaining : null;
    }

    /**
     * Most recent moment a send to DY finished (done:) or started
     * (running:) for this technician.
     */
    public function lastSentAt(string $techId, ?string $excludeBookId = null): ?Carbon
    {
        $latest = null;

        foreach (['done', 'running'] as $state) {
            $query = DirectAppointment::query()
                ->where('tech_id', $techId)
                ->where('complete_v2_calling', 'like', $state . ':' . self::TS_SHAPE);

            if ($excludeBookId) {
                $query->where('book_id', '!=', $excludeBookId);
            }

            // Fixed-width "Y-m-d H:i:s" → lexical order == chronological order.
            $marker = $query->orderByDesc('complete_v2_calling')->value('complete_v2_calling');

            if (! $marker) {
                continue;
            }

            try {
                $at = Carbon::createFromFormat('Y-m-d H:i:s', substr($marker, strlen($state) + 1));
            } catch (\Throwable $e) {
                continue; // matched the shape but isn't a real date — ignore it
            }

            if (! $at instanceof Carbon) {
                continue;
            }

            if ($latest === null || $at->timestamp > $latest->timestamp) {
                $latest = $at;
            }
        }

        return $latest;
    }
}
