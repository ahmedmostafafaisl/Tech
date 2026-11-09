<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One timestamp per tech: the last time they successfully created a transfer order. Combined with the
 * transfer_order_timer setting (seconds, global — see TransferOrderCooldown), this is all that's needed to
 * compute whether a tech is still on cooldown, and how many seconds are left.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'last_transfer_order_at')) {
                $table->timestamp('last_transfer_order_at')->nullable()->after('department');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_transfer_order_at')) {
                $table->dropColumn('last_transfer_order_at');
            }
        });
    }
};
