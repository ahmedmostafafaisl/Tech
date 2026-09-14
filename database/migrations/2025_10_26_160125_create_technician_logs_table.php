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
        Schema::create('technician_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tech_id')->nullable();
            $table->string('action_type')->nullable();
            $table->text('description')->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('action_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_logs');
    }
};
