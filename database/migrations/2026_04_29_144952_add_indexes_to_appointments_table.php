<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->index('technician_id',                      'idx_app_technician_id');
            $table->index('appointment_date',                   'idx_app_appointment_date');
            $table->index('customer_id',                        'idx_app_customer_id');
            $table->index('status',                             'idx_app_status');
            $table->index('sales_order_id',                     'idx_app_sales_order_id');
            $table->index('book_id',                            'idx_app_book_id');
            $table->index(['technician_id', 'appointment_date'], 'idx_app_tech_date');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('idx_app_technician_id');
            $table->dropIndex('idx_app_appointment_date');
            $table->dropIndex('idx_app_customer_id');
            $table->dropIndex('idx_app_status');
            $table->dropIndex('idx_app_sales_order_id');
            $table->dropIndex('idx_app_book_id');
            $table->dropIndex('idx_app_tech_date');
        });
    }
};
