<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->index('transfer_id',        'idx_to_transfer_id');
            $table->index('tech_id',            'idx_to_tech_id');
            $table->index('technician_status',  'idx_to_technician_status');
            $table->index('status',             'idx_to_status');
            $table->index('type',               'idx_to_type');
        });
    }

    public function down(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropIndex('idx_to_transfer_id');
            $table->dropIndex('idx_to_tech_id');
            $table->dropIndex('idx_to_technician_status');
            $table->dropIndex('idx_to_status');
            $table->dropIndex('idx_to_type');
        });
    }
};
