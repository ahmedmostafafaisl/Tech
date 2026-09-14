<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL requires redefining the whole enum to add one value —
        // there's no ALTER TABLE ... ADD VALUE like some other databases.
        DB::statement("
            ALTER TABLE order_leads
            MODIFY status ENUM(
                'pending',
                'q1_sent',
                'q1_answered',
                'q2_sent',
                'q2_answered',
                'scored',
                'inquiry'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        // Reverting will fail if any row currently has status = 'inquiry'
        // — reassign those rows first if you ever actually roll this back.
        DB::statement("
            ALTER TABLE order_leads
            MODIFY status ENUM(
                'pending',
                'q1_sent',
                'q1_answered',
                'q2_sent',
                'q2_answered',
                'scored'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};
