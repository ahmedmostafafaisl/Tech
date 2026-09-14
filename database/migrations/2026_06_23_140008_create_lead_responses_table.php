<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lead_id')
                ->constrained('order_leads')
                ->cascadeOnDelete();
            $table->string('ref_number', 20)->unique();
            $table->unsignedTinyInteger('question_no'); // 1 or 2
            $table->text('answer_text');
            $table->unsignedInteger('score');
            $table->timestamp('answered_at')->useCurrent();

            // A lead can only answer each question once
            $table->unique(['lead_id', 'question_no']);
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_responses');
    }
};
