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
        Schema::create('dy_payment_links', function (Blueprint $table) {
            $table->id();
            $table->enum('payment_method', ['tabby', 'clickpay', 'tamara'])->default('clickpay');
            $table->text('payment_reference_id')->nullable();
            $table->string('dy_reference_id')->nullable()->unique();
            $table->text('payment_id')->nullable();
            $table->string('status')->default('pending'); // pending, completed, failed
            $table->string('amount')->nullable();
            $table->string('phone')->nullable();
            $table->text('checkout_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dy_payment_links');
    }
};
