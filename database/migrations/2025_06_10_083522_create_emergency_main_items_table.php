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
        Schema::create('emergency_main_items', function (Blueprint $table) {
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
            $table->json('issues_reported_from_client')->nullable();
            $table->enum('item_form_type',  ['return_for_refund', 'collect_for_maintenance', 'replace_with_new'])->nullable();
            $table->enum('item_form_status', ['pending', 'rejected', 'approved'])->default('pending');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_main_items');
    }
};
