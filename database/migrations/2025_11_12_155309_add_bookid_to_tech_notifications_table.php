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
        Schema::table('tech_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('bookId')->nullable()->after('sales_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('tech_notifications', function (Blueprint $table) {
            $table->dropColumn('bookId');
        });
    }
};
