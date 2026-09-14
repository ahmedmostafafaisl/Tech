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
        Schema::create('dy_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dy_payment_id')->index();
            $table->string('name')->nullable();
            $table->text('description')->nullable();

            $table->unsignedInteger('quantity')->default(1);

            // money: use decimal (adjust precision if needed)
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);

            // rec_id from DY (often numeric but could be string; choose what matches your data)
            $table->string('rec_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dy_payment_lines');
    }
};
