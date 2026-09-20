<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('user_warehouses', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('warehouse_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('user_warehouses', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
        });
    }
};
