<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_leads', function (Blueprint $table) {
            $table->unsignedBigInteger('rec_id')->nullable()->after('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('order_leads', function (Blueprint $table) {
            $table->dropColumn('rec_id');
        });
    }
};
