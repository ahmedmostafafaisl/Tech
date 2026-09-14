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
        Schema::create('direct_appointment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('direct_appointment_id');
            $table->string('sales_order_id')->nullable();
            $table->bigInteger('SaleslineId')->nullable();
            $table->bigInteger('ProductRecId')->nullable();
            $table->string('ItemNumber')->nullable();
            $table->string('ProductName')->nullable();
            $table->bigInteger('OrderTypeRecId')->nullable();
            $table->string('OrderTypeId')->nullable();
            $table->boolean('IsPaid')->default(false);
            $table->decimal('Quantity', 10, 2)->default(1);
            $table->decimal('UnitPrice', 10, 2)->default(0);
            $table->decimal('TotalAmount', 10, 2)->default(0);
            $table->decimal('Discount', 10, 2)->default(0);
            $table->string('ItemIdCommonIssue')->nullable();
            $table->text('DescriptionCommonIssue')->nullable();
            $table->string('SalesHistoryDate')->nullable();
            $table->string('WarrantyStatus')->nullable();
            $table->bigInteger('PaymentMethodRecId')->nullable();
            $table->string('PaymentMethod')->nullable();
            $table->string('PaymentReference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_appointment_lines');
    }
};
