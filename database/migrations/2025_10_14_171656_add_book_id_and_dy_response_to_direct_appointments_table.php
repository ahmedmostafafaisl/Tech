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
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->string('tech_id')->nullable();
            $table->string('book_id')->nullable();
            $table->json('dy_response')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {});
    }
};
