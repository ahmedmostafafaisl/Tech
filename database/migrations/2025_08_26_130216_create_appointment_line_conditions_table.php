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
        Schema::create('appointment_line_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_line_id')->index();
            $table->enum('status', ['return_for_refund', 'collect_for_maintenance', 'replace_with_new']);

            // Side condition data
            foreach (['right', 'left', 'front', 'back', 'top'] as $side) {
                $table->boolean("{$side}_side_have_issue")->default(false);
                $table->json("{$side}_side_issues")->nullable(); // store array of strings
                $table->text("{$side}_side_issue_note")->nullable();
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_line_conditions');
    }
};
