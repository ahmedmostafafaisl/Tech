<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per physical NearPay card-reader terminal, created and assigned via NearPay's Accounts API (see
 * NearPayAccountsService) — nothing is written here until that API call actually succeeds.
 *
 * terminal_id is NearPay's own terminal UUID, RETURNED by Create Terminal — not something the caller
 * supplies. trsm_code is the physical hardware code the caller DOES supply (sent to NearPay to create the
 * terminal). tid is NearPay's hardware-style terminal identifier, also returned by Create Terminal. All
 * three are distinct values confirmed from NearPay's own Create Terminal response.
 *
 * assigned_user_id is nullable + unique: a terminal is either unassigned (null, any number of rows can be
 * null) or assigned to exactly one user, and a user can hold at most one terminal at a time — the unique
 * constraint on this column is what enforces that second half, so assigning an already-assigned-elsewhere
 * user a second terminal is a database-level conflict, not just an application-level check that could be
 * bypassed by a race.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nearpay_terminals', function (Blueprint $table) {
            $table->id();

            $table->string('terminal_id')->unique();
            $table->string('trsm_code');
            $table->string('tid')->nullable()->unique();

            $table->string('name')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->string('remote_user_name')->nullable();
            $table->string('remote_user_email')->nullable();
            $table->string('remote_user_mobile')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->foreignId('assigned_user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nearpay_terminals');
    }
};
