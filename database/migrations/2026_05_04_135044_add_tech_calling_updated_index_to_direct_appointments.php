<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            if (!$this->indexExists('direct_appointments', 'idx_da_tech_calling_updated')) {
                // TEXT column requires prefix length — 20 chars is enough for "done:YYYY-MM-DD HH:MM"
                DB::statement('
                ALTER TABLE `direct_appointments`
                ADD INDEX `idx_da_tech_calling_updated`
                (`tech_id`, `complete_v2_calling`(20), `updated_at`)
            ');
            }
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            if ($this->indexExists('direct_appointments', 'idx_da_tech_calling_updated')) {
                $table->dropIndex('idx_da_tech_calling_updated');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return count(DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$indexName]
        )) > 0;
    }
};
