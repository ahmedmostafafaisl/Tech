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
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'on_way',
                'on_site',
                'hold',
                'complete',
                'reschedule',
                'cancel',
                'Completed',
                'Delayed',
                'Scheduled',
                'processing'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'on_way',
                'on_site',
                'hold',
                'complete',
                'reschedule',
                'cancel',
                'Completed',
                'Delayed',
                'Scheduled'
            ])->change();
        });
    }
};
