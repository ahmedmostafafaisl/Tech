<?php

namespace App\Console\Commands;

use App\Models\DyEnvironment;
use Illuminate\Console\Command;

/**
 * Lists or switches which DY365 base URL is currently active.
 *
 * php artisan dy:environment              — lists all, marks the current default
 * php artisan dy:environment {id}         — switches to that environment
 */
class DyEnvironmentSwitch extends Command
{
    protected $signature = 'dy:environment {id? : The DyEnvironment id to switch to}';

    protected $description = 'List or switch the active DY365 base URL';

    public function handle(): int
    {
        $id = $this->argument('id');

        if ($id === null) {
            $environments = DyEnvironment::orderBy('id')->get();

            if ($environments->isEmpty()) {
                $this->warn('No environments found — run: php artisan db:seed --class=DyEnvironmentSeeder');
                return self::FAILURE;
            }

            $this->table(
                ['ID', 'Name', 'URL', 'Default?'],
                $environments->map(fn($e) => [
                    $e->id,
                    $e->name,
                    $e->url,
                    $e->is_default ? '✅' : '',
                ])
            );

            return self::SUCCESS;
        }

        $environment = DyEnvironment::find($id);

        if (!$environment) {
            $this->error("No DyEnvironment found with id {$id}.");
            return self::FAILURE;
        }

        DyEnvironment::switchTo((int) $id);

        $this->info("Switched default DY365 environment to: {$environment->name} ({$environment->url})");

        return self::SUCCESS;
    }
}
