<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * dy_payment_links.payment_method is a MySQL enum('tabby','clickpay','tamara') — a plain Schema::table()
 * column change can't add an enum value, so this uses a raw ALTER TABLE (MODIFY COLUMN), which also
 * requires re-stating every existing value, not just the new one. Only runs the raw SQL on MySQL — this
 * migration is a no-op on sqlite (used by this repo's own tests), where the column is a plain string with
 * no enum constraint to begin with.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dy_payment_links MODIFY payment_method ENUM('tabby','clickpay','tamara','alinmapay') NOT NULL DEFAULT 'clickpay'");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE dy_payment_links MODIFY payment_method ENUM('tabby','clickpay','tamara') NOT NULL DEFAULT 'clickpay'");
    }
};
