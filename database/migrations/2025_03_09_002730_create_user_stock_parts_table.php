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
        Schema::create('user_stock_parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_stock_id');
            $table->unsignedBigInteger('part_id');
            $table->string('item_number')->nullable();
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_stock_parts');
    }
};
