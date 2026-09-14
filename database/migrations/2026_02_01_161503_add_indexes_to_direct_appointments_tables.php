<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ direct_appointment_payments
        Schema::table('direct_appointment_payments', function (Blueprint $table) {
            // exists/whereHas + status filter
            $table->index(['direct_appointment_id', 'status'], 'idx_dap_appointment_status');

            // reference lookups
            $table->index('reference_id', 'idx_dap_reference_id');

            $table->index('sales_order_id', 'idx_dap_sales_order_id');

            // $table->index('payment_id', 'idx_dap_payment_id');
        });

        // ✅ direct_appointment_attachments
        Schema::table('direct_appointment_attachments', function (Blueprint $table) {
            $table->index('direct_appointment_id', 'idx_daa_appointment_id');
        });

        // ✅ direct_appointments (لو الجدول موجود)
        Schema::table('direct_appointments', function (Blueprint $table) {
            // book_id + orderByDesc(id)
            $table->index(['book_id', 'id'], 'idx_da_book_id_id');

            $table->index('sales_order_id', 'idx_da_sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointment_payments', function (Blueprint $table) {
            $table->dropIndex('idx_dap_appointment_status');
            $table->dropIndex('idx_dap_reference_id');
            $table->dropIndex('idx_dap_sales_order_id');
            // $table->dropIndex('idx_dap_payment_id');
        });

        Schema::table('direct_appointment_attachments', function (Blueprint $table) {
            $table->dropIndex('idx_daa_appointment_id');
        });

        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropIndex('idx_da_book_id_id');
            $table->dropIndex('idx_da_sales_order_id');
        });
    }
};
