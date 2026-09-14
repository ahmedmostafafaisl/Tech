<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tabby_webhooks', function (Blueprint $table) {
            $table->id();

            $table->string('webhook_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('reference_id')->nullable();

            $table->string('status')->nullable();

            $table->string('event_key')->unique();

            $table->boolean('is_test')->default(false);

            $table->json('payload');

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('payment_id');
            $table->index('reference_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tabby_webhooks');
    }
};
