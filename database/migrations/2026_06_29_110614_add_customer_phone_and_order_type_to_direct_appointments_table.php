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
            $table->string('customer_phone')->nullable()->after('book_id');
            $table->string('order_type')->nullable()->after('customer_phone');

            $table->index('customer_phone');
            $table->index('order_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_appointments', function (Blueprint $table) {
            $table->dropIndex(['customer_phone']);
            $table->dropIndex(['order_type']);

            $table->dropColumn([
                'customer_phone',
                'order_type',
            ]);
        });
    }
};
