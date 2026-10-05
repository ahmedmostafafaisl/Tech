<?php

namespace App\Services\ChangeRequest;

use App\Models\TechnicianAppointmentLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stops the same change request being sent to DY again and again while DY is
 * not answering.
 *
 * submitChangeRequest ends after ~55s with "sent, unconfirmed" instead of
 * hanging, which is right — but a technician who sees nothing confirmed taps
 * again, and every tap creates another ChangeRequest row and another DY call.
 * In the first 14 minutes of a DY slowdown, 20 appointments were sent 2-4
 * times each, 15 to 530 seconds apart. Two cases need covering:
 *
 *  1. Still in flight. A timed-out attempt stays open ~53s, so a re-tap after
 *     15-40s arrives before the first has recorded anything. claim() lets only
 *     one request at a time work on (appointment, request type). It uses a MySQL
 *     named lock — shared by every app server and freed automatically if the
 *     request dies — and falls back to an atomic Cache::add where MySQL locks
 *     aren't available. (Cache alone is not enough: with the default `file`
 *     driver each server has its own cache, so a re-tap served by the other
 *     server would not see the first request.)
 *  2. Already sent, unconfirmed. findPending() looks for a 'pending' log row
 *     for the same appointment and request type from the last 30 minutes. That
 *     lives in the database, so it holds across servers whatever the cache is.
 *
 * Neither touches the schema; both only ever suppress a REPEAT of an
 * unconfirmed request (a different request type, or one DY already answered,
 * is never blocked).
 */
class PendingChangeRequestGuard
{
    /** The cache fallback outlives a ~55s attempt slightly, then expires by itself. */
    public const INFLIGHT_SECONDS = 70;

    /** Observed retries came 15s-9min apart. */
    public const PENDING_WINDOW_MINUTES = 30;

    public static function lockKey(string $bookId, string $salesOrderId, int $requestType): string
    {
        return sprintf('change_request:inflight:%s:%s:%d', trim($bookId), trim($salesOrderId), $requestType);
    }

    /** MySQL lock names are limited to 64 characters, so use a fixed-length digest of the key. */
    public static function dbLockName(string $key): string
    {
        return 'cr:' . md5($key);
    }

    /** Does a stored log payload describe the same kind of request (cancel vs reschedule)? */
    public static function sameRequest(?array $loggedPayload, int $requestType): bool
    {
        if (! is_array($loggedPayload) || ! array_key_exists('requestType', $loggedPayload)) {
            return false;
        }

        return (int) $loggedPayload['requestType'] === $requestType;
    }

    /**
     * Try to become the only in-flight sender for this appointment + request
     * type. Returns an opaque handle to pass to release(), or null when an
     * identical request is already being sent. If neither lock mechanism is
     * usable this fails OPEN — a duplicate is better than refusing every
     * change request.
     */
    public function claim(string $bookId, string $salesOrderId, int $requestType): ?string
    {
        $key = self::lockKey($bookId, $salesOrderId, $requestType);

        // 1) MySQL named lock: true = got it, false = another request holds it, null = unavailable here
        try {
            $name = self::dbLockName($key);
            $got  = $this->acquireDbLock($name);

            if ($got === true) {
                return 'db:' . $name;
            }

            if ($got === false) {
                return null;
            }
        } catch (\Throwable $e) {
            // fall through to the cache
        }

        // 2) Atomic on redis / memcached / database caches; per-server on the `file` driver
        try {
            return Cache::add($key, now()->timestamp, self::INFLIGHT_SECONDS) ? 'cache:' . $key : null;
        } catch (\Throwable $e) {
            Log::warning('Change request in-flight lock unavailable — proceeding without it', [
                'error' => $e->getMessage(),
            ]);

            return 'cache:' . $key;
        }
    }

    public function release(?string $handle): void
    {
        if ($handle === null) {
            return;
        }

        try {
            if (str_starts_with($handle, 'db:')) {
                $stmt = DB::connection()->getPdo()->prepare('SELECT RELEASE_LOCK(?)');
                $stmt->execute([substr($handle, 3)]);
            } elseif (str_starts_with($handle, 'cache:')) {
                Cache::forget(substr($handle, 6));
            }
        } catch (\Throwable $e) {
            // a MySQL lock is freed when the connection closes; the cache entry expires on its own
        }
    }

    /** Most recent unconfirmed log row for the same appointment + request type, or null. */
    public function findPending(string $bookId, string $salesOrderId, int $requestType): ?TechnicianAppointmentLog
    {
        $rows = TechnicianAppointmentLog::query()
            ->where('book_id', $bookId)
            ->where('action', 'submit_change_request')
            ->where('status', 'pending')
            ->where('sales_order_id', $salesOrderId)
            ->where('created_at', '>=', now()->subMinutes(self::PENDING_WINDOW_MINUTES))
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        foreach ($rows as $row) {
            if (self::sameRequest($row->request_payload, $requestType)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return bool|null true = acquired, false = held by another session, null = MySQL
     *                   locks can't be used on this connection (not MySQL, or an error)
     */
    private function acquireDbLock(string $name): ?bool
    {
        // getPdo() is the connection the request's own transaction runs on
        $pdo = DB::connection()->getPdo();

        if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            return null;
        }

        $stmt = $pdo->prepare('SELECT GET_LOCK(?, 0)');
        $stmt->execute([$name]);
        $result = $stmt->fetchColumn();

        return ($result === null || $result === false) ? null : ((int) $result === 1);
    }
}
