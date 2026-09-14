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
        Schema::create('appointment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('item_rec_id');
            $table->unsignedBigInteger('sales_line_id')->nullable();
            $table->string('item_number');
            $table->string('type')->nullable();
            $table->integer('quantity');
            $table->boolean('is_paid')->default(false); // Indicates if the line is paid
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->unsignedBigInteger('line_id')->nullable();  // item id  or part id
            $table->string('line_type')->nullable();   // item or part
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            // added
            $table->string('sales_history_date')->nullable();
            $table->enum('warranty_status', ['Yes', 'No', 'None'])->default('No');
            $table->text('description_common_issues')->nullable();
            $table->text('item_common_issues')->nullable();
            $table->string('payment_method')->nullable();   // item or part

            // added fields from main_items
            $table->string('serial')->nullable();
            $table->string('floor')->nullable();
            $table->string('apart')->nullable();
            $table->string('room')->nullable();
            $table->string('code')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'unpaid'])->default('pending');
            $table->json('check_list')->nullable();
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
        Schema::dropIfExists('appointment_lines');
    }
};
