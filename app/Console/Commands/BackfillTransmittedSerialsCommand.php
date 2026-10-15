<?php

namespace App\Console\Commands;

use App\Models\DirectAppointment;
use App\Services\Serials\TransmittedSerialIndexer;
use Illuminate\Console\Command;

/**
 * One-time (or safely re-runnable) backfill for transmitted_serials, covering every appointment whose
 * dy_body was already written BEFORE this indexing existed — regardless of whether that appointment ever
 * went on to complete_flag = 1 (a serial is indexed the moment it was SENT, not only once DY365 confirms
 * it). Every new send indexes itself automatically now (see CompleteSuccessPaymentsCommand) — this is only
 * for the historical gap.
 *
 * Safe to run repeatedly: TransmittedSerialIndexer upserts on (book_id, serial), so re-running this never
 * creates duplicates.
 */
class BackfillTransmittedSerialsCommand extends Command
{
    protected $signature = 'serials:backfill-transmitted {--chunk=200}';

    protected $description = 'Populate transmitted_serials from every appointment\'s existing dy_body, sent or not yet confirmed';

    public function handle(): int
    {
        $total = 0;
        $appointments = 0;

        DirectAppointment::query()
            ->whereNotNull('dy_body')
            ->orderBy('id')
            ->chunkById((int) $this->option('chunk'), function ($chunk) use (&$total, &$appointments) {
                foreach ($chunk as $appointment) {
                    $total += TransmittedSerialIndexer::syncForAppointment($appointment);
                    $appointments++;
                }

                $this->info("Processed {$appointments} appointments so far, {$total} serial rows indexed.");
            });

        $this->info("Done. {$appointments} appointments with a dy_body scanned, {$total} serial rows indexed.");

        return Command::SUCCESS;
    }
}
