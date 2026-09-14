<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->text('complete_v2_calling')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropColumn('complete_v2_calling');
        });
    }
};
