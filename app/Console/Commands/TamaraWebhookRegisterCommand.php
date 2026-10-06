<?php

namespace App\Console\Commands;

use App\Services\Payment\TamaraWebhookException;
use App\Services\Payment\TamaraWebhookRegistrationService;
use Illuminate\Console\Command;

class TamaraWebhookRegisterCommand extends Command
{
    protected $signature = 'tamara:webhook:register {--yes : do not ask for confirmation when the target is the PRODUCTION Tamara API}';

    protected $description = 'Register (or reconcile) the Tamara webhook for this environment';

    public function handle(TamaraWebhookRegistrationService $service): int
    {
        try {
            $target = $service->preflight();

            if (
                $target['production'] && ! $this->option('yes')
                && ! $this->confirm("The Tamara API target is PRODUCTION ({$target['api_host']}) for the \"{$target['environment']}\" environment. Register the webhook there?")
            ) {
                $this->warn('Cancelled. Nothing was changed.');

                return self::FAILURE;
            }

            $result = $service->register();
        } catch (TamaraWebhookException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $webhook = $result['webhook'];

        $this->info(match ($result['action']) {
            'created'   => 'Tamara webhook registered successfully.',
            'updated'   => 'A webhook already existed with a different configuration: it was updated (no duplicate was created).',
            default     => 'Tamara webhook is already registered and up to date. Nothing was changed.',
        });
        $this->newLine();
        $this->line('Environment: ' . $webhook->environment);
        $this->line('Webhook ID:  ' . $webhook->webhook_id);
        $this->line('URL:         ' . $webhook->url);
        $this->line('Events:      ' . implode(', ', (array) $webhook->events));

        return self::SUCCESS;
    }
}
