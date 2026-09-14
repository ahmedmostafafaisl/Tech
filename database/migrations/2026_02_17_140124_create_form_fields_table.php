<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();

            // stable programmatic key: salt_home_ppm, product_front_image, ...
            $table->string('field_key')->unique();

            $table->string('label_ar');
            $table->string('label_en')->nullable();

            $table->enum('field_type', [
                'text',
                'textarea',
                'number',
                'boolean',
                'select',
                'image',
                'file',
                'datetime'
            ]);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['field_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
