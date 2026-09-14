<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Add book_id only if it doesn't exist ─────────────────────
        if (!Schema::hasColumn('direct_appointment_payments', 'book_id')) {
            Schema::table('direct_appointment_payments', function (Blueprint $table) {
                $table->string('book_id')->nullable()->after('sales_order_id');
            });
        }

        // ── Step 2: Backfill book_id from parent direct_appointments ─────────
        DB::statement('
            UPDATE direct_appointment_payments dap
            JOIN direct_appointments da ON da.id = dap.direct_appointment_id
            SET dap.book_id = da.book_id
            WHERE dap.book_id IS NULL
        ');

        // ── Step 3: Deduplicate rows before adding unique constraint ─────────
        $duplicates = DB::select("
            SELECT book_id, payment_type, reference_id
            FROM direct_appointment_payments
            WHERE book_id IS NOT NULL
              AND reference_id IS NOT NULL
            GROUP BY book_id, payment_type, reference_id
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $dup) {
            $ids = DB::table('direct_appointment_payments')
                ->where('book_id', $dup->book_id)
                ->where('payment_type', $dup->payment_type)
                ->where('reference_id', $dup->reference_id)
                ->orderByDesc('id')
                ->pluck('id')
                ->toArray();

            $keepId    = array_shift($ids);
            $deleteIds = $ids;

            Log::info('[Migration] Deduplicating direct_appointment_payments', [
                'book_id'      => $dup->book_id,
                'payment_type' => $dup->payment_type,
                'reference_id' => $dup->reference_id,
                'kept_id'      => $keepId,
                'deleted_ids'  => $deleteIds,
            ]);

            DB::table('direct_appointment_payments')
                ->whereIn('id', $deleteIds)
                ->delete();
        }

        // ── Step 4: Add indexes safely with IF NOT EXISTS ─────────────────────
        DB::statement('
            ALTER TABLE direct_appointment_payments
            ADD INDEX IF NOT EXISTS idx_dap_book_id (book_id)
        ');

        DB::statement('
            ALTER TABLE direct_appointment_payments
            ADD UNIQUE INDEX IF NOT EXISTS uniq_dap_book_type_ref (book_id, payment_type, reference_id)
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE direct_appointment_payments
            DROP INDEX IF EXISTS uniq_dap_book_type_ref
        ');

        DB::statement('
            ALTER TABLE direct_appointment_payments
            DROP INDEX IF EXISTS idx_dap_book_id
        ');

        if (Schema::hasColumn('direct_appointment_payments', 'book_id')) {
            Schema::table('direct_appointment_payments', function (Blueprint $table) {
                $table->dropColumn('book_id');
            });
        }
    }
};
