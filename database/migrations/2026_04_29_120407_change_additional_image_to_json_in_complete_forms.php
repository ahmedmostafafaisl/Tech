<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: fix existing rows that are not valid JSON ─────────────────
        DB::table('complete_forms')
            ->whereNotNull('additional_image')
            ->get(['id', 'additional_image'])
            ->each(function ($row) {
                $value = $row->additional_image;

                // skip if already valid JSON array
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return;
                }

                // wrap plain string path into a JSON array
                DB::table('complete_forms')
                    ->where('id', $row->id)
                    ->update([
                        'additional_image' => json_encode([$value]),
                    ]);
            });

        // ── Step 2: now safely change the column type ─────────────────────────
        Schema::table('complete_forms', function (Blueprint $table) {
            $table->json('additional_image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('complete_forms', function (Blueprint $table) {
            $table->string('additional_image')->nullable()->change();
        });
    }
};
