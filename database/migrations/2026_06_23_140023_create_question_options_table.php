<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedTinyInteger('question_no');
            $table->string('option_key', 5);
            $table->text('option_text');
            $table->unsignedInteger('score');

            $table->unique(['question_no', 'option_key']);
        });

        // Seed Question 1 — booking readiness
        DB::table('question_options')->insert([
            ['id' => Str::uuid(), 'question_no' => 1, 'option_key' => 'A', 'option_text' => 'Ready for booking and installation', 'score' => 40],
            ['id' => Str::uuid(), 'question_no' => 1, 'option_key' => 'B', 'option_text' => 'I need to confirm it suits my needs',  'score' => 30],
            ['id' => Str::uuid(), 'question_no' => 1, 'option_key' => 'C', 'option_text' => 'I have questions before ordering',    'score' => 20],
        ]);

        // Seed Question 2 — preferred contact time
        DB::table('question_options')->insert([
            ['id' => Str::uuid(), 'question_no' => 2, 'option_key' => 'A', 'option_text' => 'Call within minutes', 'score' => 50],
            ['id' => Str::uuid(), 'question_no' => 2, 'option_key' => 'B', 'option_text' => 'Within one hour',     'score' => 35],
            ['id' => Str::uuid(), 'question_no' => 2, 'option_key' => 'C', 'option_text' => 'Later today',         'score' => 20],
            ['id' => Str::uuid(), 'question_no' => 2, 'option_key' => 'D', 'option_text' => 'WhatsApp only',        'score' =>  5],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};
