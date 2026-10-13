<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors tabby_webhooks exactly: one row per distinct webhook delivery, `event_key` unique for dedup
 * (NearPay's webhook docs do not document a separate event-id field, so — same as Tabby — the key is a
 * fingerprint of the payload itself), `processed_at` for idempotency once the queued job has acted on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nearpay_webhooks', function (Blueprint $table) {
            $table->id();

            // The webhook payload's top-level "id" (a UUID) — believed, but not explicitly documented, to
            // be the same identifier GET /banks-sdk/transactions/{id} expects. See NearPayService.
            $table->string('transaction_id')->nullable();

            $table->boolean('is_approved')->nullable();
            $table->boolean('is_refunded')->nullable();
            $table->boolean('is_reversed')->nullable();

            $table->string('event_key')->unique();

            $table->json('payload');

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nearpay_webhooks');
    }
};
