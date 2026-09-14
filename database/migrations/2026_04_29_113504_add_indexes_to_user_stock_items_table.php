<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_stock_items', function (Blueprint $table) {
            $table->index('user_stock_id',              'idx_usi_user_stock_id');
            $table->index('item_id',                    'idx_usi_item_id');
            $table->index(['user_stock_id', 'item_id'], 'idx_usi_user_stock_item');
        });
    }

    public function down(): void
    {
        Schema::table('user_stock_items', function (Blueprint $table) {
            $table->dropIndex('idx_usi_user_stock_id');
            $table->dropIndex('idx_usi_item_id');
            $table->dropIndex('idx_usi_user_stock_item');
        });
    }
};
