<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_leads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number', 50)->unique();
            $table->string('customer_name', 150)->nullable();
            $table->string('mobile_number', 20);
            $table->string('product', 200)->nullable();
            $table->enum('status', [
                'pending',
                'q1_sent',
                'q1_answered',
                'q2_sent',
                'q2_answered',
                'scored',
            ])->default('pending');
            $table->string('q1_message_id')->nullable();
            $table->string('q2_message_id')->nullable();
            $table->unsignedInteger('total_score')->default(0);
            $table->enum('priority', ['hot', 'warm', 'cold'])->nullable();
            $table->timestamps();

            $table->index('mobile_number');
            $table->index('status');
            $table->index('priority');
            $table->index(['total_score', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_leads');
    }
};
