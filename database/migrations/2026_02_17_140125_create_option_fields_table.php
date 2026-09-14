<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('option_fields', function (Blueprint $table) {
            $table->id();

            $table->foreignId('option_id')
                ->constrained('appointment_type_options')
                ->cascadeOnDelete();

            $table->foreignId('field_id')
                ->constrained('form_fields')
                ->cascadeOnDelete();

            // TRUE / 1 in sheet => required
            $table->boolean('is_required')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['option_id', 'field_id']);
            $table->index(['option_id', 'is_active']);
            $table->index(['field_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('option_fields');
    }
};
