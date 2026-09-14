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
        Schema::table('transfer_orders', function (Blueprint $table) {

            $table->json('dy_response')->nullable()->after('to_warehouse_rec');
            $table->boolean('flag')->default(false)->after('dy_response');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropColumn(['flag', 'dy_response']);
        });
    }
};
