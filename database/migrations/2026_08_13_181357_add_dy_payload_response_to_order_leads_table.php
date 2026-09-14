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
        Schema::table('order_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('order_leads', 'dy_payload')) {
                $table->text('dy_payload')->nullable()->after('id');
            }

            if (!Schema::hasColumn('order_leads', 'dy_response')) {
                $table->text('dy_response')->nullable()->after('dy_payload');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_leads', function (Blueprint $table) {
            $table->dropColumn(['dy_payload', 'dy_response']);
        });
    }
};
