<?php
// ===== create_appointment_transaction_lines_table =====

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_transaction_id')
                ->constrained('appointment_transactions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('sales_line_rec_id')->comment('e.g. 5641980324');
            $table->string('item_number')->comment('e.g. FCRA63');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('order_type_rec_id')->comment('e.g. 5638137446');
            $table->enum('warranty_status', ['Yes', 'No', 'None'])->default('None');
            $table->timestamps();

            $table->unique('sales_line_rec_id');
            $table->index('item_number');
            $table->index('appointment_transaction_id');
            $table->index(['appointment_transaction_id', 'item_number'], 'atl_transaction_item_idx');
            $table->index(['appointment_transaction_id', 'sales_line_rec_id'], 'atl_transaction_salesline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_transaction_lines');
    }
};
