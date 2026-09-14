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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('technician_id');
            $table->string('type')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'completed', 'canceled'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high'])->default('low');
            $table->dateTime('due_date')->nullable();
            $table->string('icon')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
