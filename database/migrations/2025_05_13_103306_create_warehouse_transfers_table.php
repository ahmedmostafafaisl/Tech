<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['tech_to_warehouse', 'warehouse_to_tech', 'tech_to_tech'])->default('warehouse_to_tech');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('tech_id');
            $table->dateTime('date')->nullable();
            $table->enum('status', ['pending', 'processing', 'received', 'rejected'])->default('pending');
            $table->text('reference_id')->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfers');
    }
};
