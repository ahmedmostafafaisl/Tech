<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_types', function (Blueprint $table) {
            $table->id();

            // periodic / service / emergency
            $table->string('code')->unique();

            $table->string('name_ar');
            $table->string('name_en');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_types');
    }
};
