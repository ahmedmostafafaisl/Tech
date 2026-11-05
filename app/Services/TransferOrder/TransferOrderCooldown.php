<?php

namespace App\Services\TransferOrder;

use App\Models\Setting;
use App\Models\User;

/**
 * One global timer (transfer_order_timer, in seconds — same Setting mechanism as
 * appointment_cooldown_minutes/TechnicianSendCooldown) applied per-technician: a tech who just created a
 * transfer order must wait that many seconds before creating another. Unlike TechnicianSendCooldown, which
 * derives "last sent" from a status marker string already on DirectAppointment, there's no equivalent
 * existing signal here, so this uses a dedicated column (users.last_transfer_order_at) instead.
 */
class TransferOrderCooldown
{
    public function cooldownSeconds(): int
    {
        return max(0, (int) Setting::get('transfer_order_timer', 0));
    }

    /**
     * Seconds left before this technician may create another transfer order, or null when they are free to
     * (no cooldown configured, no prior transfer order, or the cooldown has already elapsed).
     */
    public function remainingSeconds(string $techId): ?int
    {
        $cooldown = $this->cooldownSeconds();

        if ($cooldown <= 0) {
            return null;
        }

        $lastAt = User::where('tech_id', $techId)->value('last_transfer_order_at');

        if ($lastAt === null) {
            return null;
        }

        $remaining = $cooldown - (now()->timestamp - \Carbon\Carbon::parse($lastAt)->timestamp);

        return $remaining > 0 ? (int) $remaining : null;
    }

    /**
     * Marks "now" as this technician's most recent transfer order — called only after a transfer order is
     * actually, successfully created, so a rejected or failed attempt never starts (or restarts) the clock.
     */
    public function recordCreated(string $techId): void
    {
        User::where('tech_id', $techId)->update(['last_transfer_order_at' => now()]);
    }
}
