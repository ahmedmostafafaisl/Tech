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
        Schema::create('periodic_main_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('item_id');
            $table->string('serial')->nullable();
            $table->string('floor')->nullable();
            $table->string('apart')->nullable();
            $table->string('room')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->integer('quantity')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sub_total_price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->enum('payment_type', ['cash', 'online_payment', 'tabby', 'tamara', 'apply_pay', 'pay_with_package'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'unpaid'])->default('pending');
            $table->json('check_list')->nullable();
            $table->boolean('valid_warranty')->default('1');
            $table->boolean('paid_service')->default('1');
            $table->boolean('missing')->default('1');
            $table->string('maintenance_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodic_main_items');
    }
};
