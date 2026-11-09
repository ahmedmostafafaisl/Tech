<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DY365's own CustomerId — the key tying an appointment to a specific customer across multiple visits (it's
 * what NewDirectIntegrationController's existing "related appointments" filtering already uses when live
 * data comes straight from DY365, but it was never persisted locally until now). Needed here specifically so
 * a customer's appointments can be looked up directly from the local table (see the new tech+customer
 * endpoint), without a live DY365 call every time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('direct_appointments', 'customer_id')) {
                $table->string('customer_id')->nullable()->after('tech_id');
                $table->index(['tech_id', 'customer_id']);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('direct_appointments', 'customer_id')) {
            return;
        }

        try {
            Schema::table('direct_appointments', fn(Blueprint $table) => $table->dropIndex(['tech_id', 'customer_id']));
        } catch (\Throwable $e) {
            // index may not exist under this name on every driver — fine, drop the column regardless
        }

        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });
    }
};
