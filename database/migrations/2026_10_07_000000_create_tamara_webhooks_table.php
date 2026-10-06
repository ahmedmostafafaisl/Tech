<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per webhook this application registers with Tamara (history is kept: a deleted webhook is marked inactive,
 * never removed). `secret` holds the generated value Tamara echoes back in the Authorization header of every delivery;
 * it is stored ENCRYPTED (Laravel's `encrypted` cast = Crypt::encryptString) and never leaves the server.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tamara_webhooks')) {
            return;
        }

        Schema::create('tamara_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('webhook_id')->nullable()->unique();      // Tamara's id; null while a registration is in flight
            $table->string('environment', 50);                       // app environment that owns it (production, staging, ...)
            $table->string('api_host');                              // Tamara API host it was registered at (sandbox vs production)
            $table->string('type', 50)->default('order');
            $table->text('url');
            $table->json('events');
            $table->text('secret');                                   // ENCRYPTED
            $table->string('status', 30)->default('pending');        // pending | active | failed | deleted | missing_remote
            $table->boolean('active')->default(false);
            $table->json('remote_payload')->nullable();              // Tamara's reply with any secret/headers stripped
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();

            $table->index(['environment', 'api_host', 'active'], 'tamara_webhooks_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tamara_webhooks');
    }
};
