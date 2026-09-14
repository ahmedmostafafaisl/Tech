<?php
// ===== create_appointment_transaction_serials_table =====

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_transaction_serials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_transaction_line_id');
            $table->unsignedBigInteger('sales_line_rec_id');
            $table->string('item_number');
            $table->string('serial');
            $table->timestamps();

            $table->foreign('appointment_transaction_line_id', 'ats_line_id_foreign')
                ->references('id')
                ->on('appointment_transaction_lines')
                ->cascadeOnDelete();

            $table->unique(['sales_line_rec_id', 'serial'], 'ats_salesline_serial_unique');
            $table->index('sales_line_rec_id', 'ats_salesline_idx');
            $table->index('serial', 'ats_serial_idx');
            $table->index(['appointment_transaction_line_id', 'serial'], 'ats_line_serial_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_transaction_serials');
    }
};
