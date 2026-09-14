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
        Schema::create('app_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version_name'); // e.g., "1.0.3"
            $table->string('platform'); // e.g., "android" or "ios"
            $table->boolean('force_update')->default(false); // whether update is mandatory
            $table->text('release_notes')->nullable(); // changelog or notes
            $table->string('download_url')->nullable(); // URL to app update
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_versions');
    }
};
