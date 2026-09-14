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
        Schema::create('direct_appointment_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('direct_appointment_id');
            $table->string('sales_order_id');
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->string('payment_type')->nullable();
            $table->string('phone')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_appointment_payments');
    }
};
