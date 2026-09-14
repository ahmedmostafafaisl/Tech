<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_form_submissions', function (Blueprint $table) {
            $table->id();

            // ✅ extra identifiers
            $table->string('sales_order_id')->nullable();
            $table->string('book_id')->nullable();

            // optional link to appointments table if exists
            $table->foreignId('appointment_id')->nullable()
                ->constrained('direct_appointments')->nullOnDelete();

            $table->foreignId('appointment_type_id')
                ->constrained('appointment_types')->cascadeOnDelete();

            // periodic/service => option
            // emergency => device
            $table->foreignId('root_option_id')
                ->constrained('appointment_type_options')->cascadeOnDelete();

            // emergency only
            $table->foreignId('problem_option_id')->nullable()
                ->constrained('appointment_type_options')->nullOnDelete();

            $table->foreignId('solution_option_id')->nullable()
                ->constrained('appointment_type_options')->nullOnDelete();

            $table->foreignId('submitted_by')->nullable()
                ->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['appointment_type_id', 'root_option_id'], 'afs_type_root_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_form_submissions');
    }
};
