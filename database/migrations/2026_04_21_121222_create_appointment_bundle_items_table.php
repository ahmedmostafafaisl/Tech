<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // create_appointment_bundle_items_table
    public function up(): void
    {
        Schema::create('appointment_bundle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_bundle_id')->constrained('appointment_bundles')->onDelete('cascade');
            $table->string('item_number');
            $table->string('item_name')->nullable();
            $table->integer('quantity');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('order_type_rec_id');
            $table->string('warranty_status')->default('None');
            $table->string('payment_method')->default('CASH');
            $table->timestamps();

            // indexes
            $table->index('appointment_bundle_id');
            $table->index('item_number');
            $table->index(['appointment_bundle_id', 'item_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_bundle_items');
    }
};
