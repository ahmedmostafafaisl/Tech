<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{


    public function up(): void
    {
        Schema::table('direct_appointment_lines', function (Blueprint $table) {
            $table->integer('max_quantity')->nullable()->after('Quantity');
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointment_lines', function (Blueprint $table) {
            $table->dropColumn('max_quantity');
        });
    }
};
