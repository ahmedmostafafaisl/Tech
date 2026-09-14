<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_appointment_messages', function (Blueprint $table) {
            $table->index('appointment_id', 'idx_pam_appointment_id');
            $table->index('sales_order',    'idx_pam_sales_order');
            $table->index('book_id',        'idx_pam_book_id');
            $table->index('created_at',     'idx_pam_created_at');
            $table->index('id',             'idx_pam_id');
        });
    }

    public function down(): void
    {
        Schema::table('pre_appointment_messages', function (Blueprint $table) {
            $table->dropIndex('idx_pam_appointment_id');
            $table->dropIndex('idx_pam_sales_order');
            $table->dropIndex('idx_pam_book_id');
            $table->dropIndex('idx_pam_created_at');
            $table->dropIndex('idx_pam_id');
        });
    }
};
