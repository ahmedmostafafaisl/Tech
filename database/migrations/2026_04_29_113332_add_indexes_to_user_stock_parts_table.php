<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_stock_parts', function (Blueprint $table) {
            $table->index('user_stock_id',              'idx_usp_user_stock_id');
            $table->index('part_id',                    'idx_usp_part_id');
            $table->index(['user_stock_id', 'part_id'], 'idx_usp_user_stock_part');
        });
    }

    public function down(): void
    {
        Schema::table('user_stock_parts', function (Blueprint $table) {
            $table->dropIndex('idx_usp_user_stock_id');
            $table->dropIndex('idx_usp_part_id');
            $table->dropIndex('idx_usp_user_stock_part');
        });
    }
};
