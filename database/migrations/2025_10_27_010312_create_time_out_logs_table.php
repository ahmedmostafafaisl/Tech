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
        Schema::create('time_out_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tech_id')->nullable(); // technician ID
            $table->string('route')->nullable(); // route name or endpoint
            $table->text('body')->nullable(); // request body or data
            $table->string('time')->nullable(); // time of log
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_out_logs');
    }
};
