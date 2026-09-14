<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tamara_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('sales_line_id')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('reference_id')->nullable(); // Tamara order_reference_id
            $table->string('order_number')->nullable(); // internal order
            $table->text('checkout_url')->nullable(); // Tamara redirect url
            $table->string('status')->nullable()->default('created');
            $table->decimal('amount', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tamara_payments');
    }
};
