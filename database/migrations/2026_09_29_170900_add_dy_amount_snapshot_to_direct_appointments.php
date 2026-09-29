<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('direct_appointments', 'total_amount_sum')) {
                $table->decimal('total_amount_sum', 10, 2)->nullable()->after('required_amount');
            }

            if (!Schema::hasColumn('direct_appointments', 'dy_required_amount')) {
                $table->decimal('dy_required_amount', 10, 2)->nullable()->after('total_amount_sum');
            }

            if (!Schema::hasColumn('direct_appointments', 'used_balance')) {
                $table->decimal('used_balance', 10, 2)->nullable()->after('dy_required_amount');
            }

            if (!Schema::hasColumn('direct_appointments', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->nullable()->after('used_balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            foreach (['total_amount_sum', 'dy_required_amount', 'used_balance', 'paid_amount'] as $column) {
                if (Schema::hasColumn('direct_appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
