<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_appointment_lines', function (Blueprint $table) {
            $table->index('direct_appointment_id', 'dal_direct_appointment_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointment_lines', function (Blueprint $table) {
            $table->dropIndex('dal_direct_appointment_id_index');
        });
    }
};
