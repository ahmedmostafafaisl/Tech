<?php

namespace App\Jobs;

use App\Models\NearPayTerminal;
use App\Services\Payment\NearPayTerminalSyncService;
use App\Services\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs NearPayTerminalSyncService::syncAll() in the background — moved off the request/response cycle
 * entirely, since NearPay's own GET /terminals is paginated and syncAll() pages through every page
 * sequentially: with enough terminals, that total wait time can exceed typical web-server and edge-proxy
 * timeouts (confirmed in production as a Cloudflare 502 "origin_bad_gateway" — PHP-FPM getting killed by
 * max_execution_time mid-response looks exactly like an invalid/incomplete response to anything in front of
 * it). A queue worker has no such request-lifetime constraint, so the same sync logic simply has as long as
 * it needs here.
 */
class SyncNearPayTerminalsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function handle(NearPayTerminalSyncService $sync): void
    {
        try {
            $result = $sync->syncAll();
        } catch (\Throwable $e) {
            Log::warning('SyncNearPayTerminalsJob: sync failed', ['error' => $e->getMessage()]);

            TelegramService::send(
                "⚠️ NearPay terminal sync failed\n\n"
                    . "error: {$e->getMessage()}"
            );

            return;
        }

        TelegramService::send(
            "✅ NearPay terminal sync finished\n\n"
                . "synced: {$result['synced']}\n"
                . "linked: {$result['linked']}"
        );
    }
}
