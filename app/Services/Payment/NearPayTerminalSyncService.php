<?php

namespace App\Services\Payment;

use App\Models\NearPayTerminal;
use App\Models\User;

/**
 * Pulls every terminal from NearPay's own GET /terminals and reconciles it into the local nearpay_terminals
 * table — NearPay is the source of truth here, since a terminal can be assigned directly through NearPay's
 * own dashboard, not only through this app's assign/unassign endpoints. A terminal NearPay knows about but
 * this app has never seen (created outside this app) is inserted, not skipped.
 */
class NearPayTerminalSyncService
{
    public function __construct(private readonly NearPayAccountsService $nearPay) {}

    /**
     * @return array{synced: int, linked: int} how many terminal rows were written, and how many of those
     *                                          got linked to a local user by matching email/mobile
     */
    public function syncAll(): array
    {
        $synced = 0;
        $linked = 0;
        $page = 1;

        do {
            $result = $this->nearPay->listTerminals($page);

            foreach ($result['terminals'] as $remote) {
                if ($this->syncOne($remote)) {
                    $linked++;
                }
                $synced++;
            }

            $totalPages = (int) ($result['pages']['total'] ?? 1);
            $page++;
        } while ($page <= $totalPages);

        return ['synced' => $synced, 'linked' => $linked];
    }

    /**
     * @return bool  whether this terminal ended up linked to a local user record
     */
    private function syncOne(array $remote): bool
    {
        if (empty($remote['id'])) {
            return false;
        }

        $remoteUser = $remote['user'] ?? null;

        $attributes = [
            'tid'                 => $remote['tid'] ?? null,
            'name'                => $remote['name'] ?? null,
            'is_connected'        => (bool) ($remote['is_connected'] ?? false),
            'remote_user_name'    => $remoteUser['name'] ?? null,
            'remote_user_email'   => $remoteUser['email'] ?? null,
            'remote_user_mobile'  => $remoteUser['mobile'] ?? null,
            'last_synced_at'      => now(),
        ];

        // A terminal whose trsm_code this app never recorded (created directly through NearPay's own
        // dashboard, not via this app's create endpoint) still gets a row — trsm_code is NearPay's own tid
        // as a fallback, since the real trsm_code isn't returned by List Terminals, only by Create Terminal.
        $terminal = NearPayTerminal::firstOrNew(['terminal_id' => $remote['id']]);

        if (! $terminal->exists) {
            $terminal->trsm_code = $remote['tid'] ?? $remote['id'];
        }

        $terminal->fill($attributes);

        $linkedUser = $this->findLocalUser($remoteUser);

        if ($linkedUser) {
            $terminal->assigned_user_id = $linkedUser->id;
            $terminal->assigned_at = $terminal->assigned_at ?? now();
        } elseif (empty($remoteUser)) {
            $terminal->assigned_user_id = null;
            $terminal->assigned_at = null;
        }
        // else: NearPay reports an assignment, but no local user matches it (remote_user_* above still
        // records who NearPay says it is) — assigned_user_id is left as whatever it already was locally,
        // rather than guessed at or cleared.

        $terminal->save();

        return $linkedUser !== null;
    }

    /**
     * Matches NearPay's reported user back to a local User by email first, then mobile — these are the only
     * two identifiers NearPay's own response shares with this app's own user records (NearPay's internal
     * user id is its own, unrelated to this app's).
     */
    private function findLocalUser(?array $remoteUser): ?User
    {
        if (empty($remoteUser)) {
            return null;
        }

        if (! empty($remoteUser['email'])) {
            $user = User::where('email', $remoteUser['email'])->first();
            if ($user) {
                return $user;
            }
        }

        if (! empty($remoteUser['mobile'])) {
            $user = User::where('phone', $remoteUser['mobile'])->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }
}
