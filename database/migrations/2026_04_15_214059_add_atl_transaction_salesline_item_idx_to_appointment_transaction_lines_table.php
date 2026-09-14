<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('appointment_transaction_lines', function (Blueprint $table) {
            $table->index(
                ['appointment_transaction_id', 'sales_line_rec_id', 'item_number'],
                'atl_transaction_salesline_item_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('appointment_transaction_lines', function (Blueprint $table) {
            $table->dropIndex('atl_transaction_salesline_item_idx');
        });
    }
};
