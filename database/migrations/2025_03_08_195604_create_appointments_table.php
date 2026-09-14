<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('technician_id');
            $table->unsignedBigInteger('address_id')->nullable();
            $table->text('appointment_num')->nullable();
            $table->enum('type', ['installation', 'periodic', 'emergency', 'complaint', 'service'])->default('installation');
            $table->text('type_rec_id')->nullable();
            $table->string('maintenance_type')->nullable();
            $table->string('phone')->nullable();
            $table->string('whats_app')->nullable();
            $table->string('alternative_number')->nullable();
            $table->string('address')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('branch')->nullable();
            $table->string('sector')->nullable();
            $table->dateTime('appointment_date')->nullable();
            $table->dateTime('installation_date')->nullable();
            $table->string('appointment_time')->nullable();
            $table->decimal('sub_total_price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('paid', 10, 2)->nullable();
            $table->decimal('collect', 10, 2)->nullable();
            $table->string('service_type')->nullable();
            $table->string('cancel_notes')->nullable();
            $table->string('reschedule_notes')->nullable();
            $table->string('hold_reason')->nullable();
            $table->enum('status', ['pending', 'on_way', 'on_site', 'hold', 'complete', 'reschedule', 'cancel', 'Completed', 'Delayed', 'Scheduled'])->default('pending');
            $table->enum('billing_status', ['pending', 'paid', 'refunded', 'unpaid'])->default('pending');
            $table->boolean('power_socket')->default(0);
            $table->string('complete_otp')->nullable();
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('sales_order_id')->nullable();
            $table->unsignedBigInteger('rec_id')->nullable();
            // added
            $table->enum('customer_confirmation_status', ['Yes', 'No', 'None'])->default('No');
            $table->text('description_common_issues')->nullable();
            $table->text('item_common_issues')->nullable();
            $table->text('order_notes')->nullable();
            $table->enum('warranty_status', ['Yes', 'No', 'None'])->default('No');
            $table->text('notes_optional')->nullable();
            // new
            $table->string('book_id')->nullable();
            $table->string('tech_status')->nullable();
            // dy completed appointments
            $table->boolean('dy_completed')->default(0);



            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
