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
        Schema::create('transfer_order_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_order_id');
            $table->string('item_number');
            $table->unsignedBigInteger('item_rec_id');
            $table->integer('quantity')->nullable();
            $table->integer('requested_quantity')->nullable();
            $table->integer('transferred_quantity')->nullable();
            $table->enum('type', ['item', 'part', 'new'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_order_lines');
    }
};
