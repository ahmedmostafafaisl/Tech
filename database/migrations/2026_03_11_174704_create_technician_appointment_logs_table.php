<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_appointment_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('tech_id')->nullable()->index();

            $table->string('book_id')->nullable()->index();
            $table->string('sales_order_id')->nullable()->index();

            $table->string('action', 100)->index();
            $table->string('status', 50)->index();

            $table->text('message')->nullable();
            $table->longText('error')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['book_id', 'action']);
            $table->index(['tech_id', 'book_id']);
            $table->index('created_at');
            $table->index(['book_id', 'created_at']);
            $table->index(['tech_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_appointment_logs');
    }
};
