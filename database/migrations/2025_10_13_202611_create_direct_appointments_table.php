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
        Schema::create('direct_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('sales_order_id')->nullable();
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->boolean('complete_flag')->default(false);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_appointments');
    }
};
