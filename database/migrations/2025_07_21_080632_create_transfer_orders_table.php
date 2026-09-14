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
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transfer_rec_id')->nullable();
            $table->unsignedBigInteger('tech_id');
            $table->string('transfer_id')->nullable();
            $table->enum('type', ['TechnicianToWarehouse', 'WarehouseToTechnician', 'TechToTech'])->default('WarehouseToTechnician');
            $table->unsignedBigInteger('warehouse_id');
            $table->dateTime('date')->nullable();
            $table->enum('status', ['Created', 'Shipped', 'Received'])->default('Created');
            $table->enum('technician_status', ['Confirmed',  'Rejected', 'Drafted'])->default('Drafted');
            $table->string('from_warehouse')->nullable();
            $table->string('to_warehouse')->nullable();
            $table->string('from_warehouse_rec')->nullable();
            $table->string('to_warehouse_rec')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_orders');
    }
};
