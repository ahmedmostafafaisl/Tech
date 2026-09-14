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
        Schema::create('evaluation_messages', function (Blueprint $table) {
            $table->id();

            $table->string('phone')->index();
            $table->string('book_id')->unique();
            $table->string('order_type')->index();
            $table->boolean('sent')->default(false)->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluation_messages');
    }
};
