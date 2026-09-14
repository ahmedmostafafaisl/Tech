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
        Schema::table('pre_appointment_messages', function (Blueprint $table) {
            $table->json('dy_response')->nullable()->after('response');
            $table->boolean('flag')->default(false)->after('dy_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_appointment_messages', function (Blueprint $table) {
            $table->dropColumn(['dy_response', 'flag']);
        });
    }
};
