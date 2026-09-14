<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_transactions', function (Blueprint $table) {
            $table->index(['tech_id', 'created_at'], 'idx_at_tech_created');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_at_tech_created');
        });
    }
};
