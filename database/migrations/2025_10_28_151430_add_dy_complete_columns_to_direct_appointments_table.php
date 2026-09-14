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
            $table->longText('dy_attachment_body')->nullable()->after('dy_body');
            $table->longText('dy_attachment_response')->nullable()->after('dy_attachment_body');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropColumn(['dy_attachment_body', 'dy_attachment_response']);
        });
    }
};
