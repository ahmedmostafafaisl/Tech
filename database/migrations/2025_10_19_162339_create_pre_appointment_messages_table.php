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
        Schema::create('pre_appointment_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->nullable();
            $table->string('sales_order')->nullable();
            $table->string('book_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('worker_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name')->nullable();
            $table->string('type')->default('Service')->nullable();
            $table->string('date')->nullable();
            $table->text('items')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->text('response')->nullable();
            $table->enum('customer_response', ['pending', 'confirm', 'reschedule', 'cancel'])
                ->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_appointment_messages');
    }
};
