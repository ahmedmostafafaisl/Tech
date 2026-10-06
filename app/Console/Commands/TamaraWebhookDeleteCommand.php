<?php

namespace App\Console\Commands;

use App\Services\Payment\TamaraWebhookException;
use App\Services\Payment\TamaraWebhookRegistrationService;
use Illuminate\Console\Command;

/**
 * Deletes the active Tamara webhook at Tamara, then marks the local record inactive (the record is kept).
 *
 *   php artisan tamara:webhook:delete
 *
 * Always asks for confirmation; --yes is the explicit non-interactive confirmation.
 */
class TamaraWebhookDeleteCommand extends Command
{
    protected $signature = 'tamara:webhook:delete {--yes : confirm the deletion without prompting}';

    protected $description = 'Delete the active Tamara webhook for this environment (the local record is kept, marked inactive)';

    public function handle(TamaraWebhookRegistrationService $service): int
    {
        try {
            $webhook = $service->activeRecord();
        } catch (TamaraWebhookException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($webhook === null) {
            $this->info('No active Tamara webhook is recorded for this environment. Nothing to delete.');

            return self::SUCCESS;
        }

        $this->line('Environment: ' . $webhook->environment);
        $this->line('Webhook ID:  ' . $webhook->webhook_id);
        $this->line('URL:         ' . $webhook->url);
        $this->newLine();

        if (! $this->option('yes') && ! $this->confirm('Delete this webhook at Tamara? Tamara will stop sending notifications to it.')) {
            $this->warn('Cancelled. Nothing was deleted.');

            return self::FAILURE;
        }

        try {
            $service->delete();
        } catch (TamaraWebhookException $e) {
            $this->error($e->getMessage());
            $this->line('The local record was NOT changed.');

            return self::FAILURE;
        }

        $this->info('Tamara webhook deleted. The local record was kept and marked inactive.');

        return self::SUCCESS;
    }
}
