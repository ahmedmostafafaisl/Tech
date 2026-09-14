<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('serial')->nullable();
            $table->string('code')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('quantity')->nullable();

            // added by Faisal
            $table->unsignedBigInteger('rec_id')->nullable();
            $table->string('item_number')->nullable();
            $table->string('site_id')->nullable();
            $table->string('location_id')->nullable();
            $table->string('dy_id')->nullable();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
