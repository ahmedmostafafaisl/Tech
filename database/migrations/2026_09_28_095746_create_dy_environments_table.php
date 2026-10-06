<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dy_environments', function (Blueprint $table) {
            $table->id();
            $table->string('name');       // e.g. "Production", "UAT", "Dev 1"
            $table->string('url');        // e.g. https://hamat-prod.operations.eu.dynamics.com
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dy_environments');
    }
};
