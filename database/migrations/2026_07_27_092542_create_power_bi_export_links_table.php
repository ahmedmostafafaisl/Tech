<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('power_bi_export_links', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7)->unique(); // 'Y-m', e.g. '2026-07'
            $table->text('download_url');
            $table->timestamp('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('power_bi_export_links');
    }
};
