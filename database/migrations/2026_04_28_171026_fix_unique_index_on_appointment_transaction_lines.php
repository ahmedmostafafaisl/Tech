<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{


    public function up(): void
    {
        Schema::table('appointment_transaction_lines', function (Blueprint $table) {
            // Drop the single-column unique index
            $table->dropUnique('appointment_transaction_lines_sales_line_rec_id_unique');
            // Add composite unique — same sales_line_rec_id can exist in different transactions
            $table->unique(['appointment_transaction_id', 'sales_line_rec_id'], 'atl_transaction_salesline_unique');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_transaction_lines', function (Blueprint $table) {
            $table->dropUnique('atl_transaction_salesline_unique');
            $table->unique('sales_line_rec_id');
        });
    }
};
