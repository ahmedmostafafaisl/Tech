<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_type_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appointment_type_id')
                ->constrained('appointment_types')
                ->cascadeOnDelete();

            // For hierarchy (Emergency):
            // Level1 (device): parent_id = null
            // Level2 (problem): parent_id = device_id
            // Level3 (solution): parent_id = problem_id
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('appointment_type_options')
                ->cascadeOnDelete();

            $table->string('label_ar');
            $table->string('label_en')->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['appointment_type_id', 'parent_id']);
            $table->index(['appointment_type_id', 'is_active']);
            $table->index(['parent_id', 'is_active']);

            // prevent duplicate label under same parent within same appointment type
            $table->unique(['appointment_type_id', 'parent_id', 'label_ar'], 'uniq_option_per_parent_ar');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_type_options');
    }
};
