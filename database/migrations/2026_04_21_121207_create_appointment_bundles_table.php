<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // create_appointment_bundles_table
    public function up(): void
    {
        Schema::create('appointment_bundles', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_id');
            $table->string('book_id');
            $table->string('sales_order_id');
            $table->string('bundle_id');
            $table->string('bundle_name')->nullable();
            $table->integer('quantity');
            $table->string('order_type_rec_id');
            $table->enum('status', ['pending', 'added', 'failed'])->default('pending');
            $table->timestamps();

            // indexes
            $table->index('appointment_id');
            $table->index(['book_id', 'status']);
            $table->index('sales_order_id');
            $table->index('bundle_id');
            $table->index('status');
            $table->index(['appointment_id', 'bundle_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('appointment_bundles');
    }
};
