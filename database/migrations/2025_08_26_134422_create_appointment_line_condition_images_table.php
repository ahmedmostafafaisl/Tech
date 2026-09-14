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
        Schema::create('appointment_line_condition_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('condition_id');
            $table->string('side'); // 'right', 'left', 'front', 'back', 'top'
            $table->string('path'); // file path or URL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_line_condition_images');
    }
};
