<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_form_submission_values', function (Blueprint $table) {
            $table->id();

            $table->foreignId('submission_id')
                ->constrained('appointment_form_submissions')->cascadeOnDelete();

            $table->foreignId('field_id')
                ->constrained('form_fields')->cascadeOnDelete();

            $table->longText('value_text')->nullable();
            $table->decimal('value_number', 12, 3)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();

            $table->timestamps();

            // one value per field per submission
            $table->unique(['submission_id', 'field_id'], 'afsv_sub_field_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_form_submission_values');
    }
};
