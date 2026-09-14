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
            $table->text('notes')->nullable()->after('status');
            $table->decimal('collect', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropColumn('notes');
            $table->dropColumn('collect');
        });
    }
};
