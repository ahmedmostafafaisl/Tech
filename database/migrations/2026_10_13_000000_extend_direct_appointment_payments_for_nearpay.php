<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * NearPay needs two states this table's `status` enum never had to represent before: a transaction that
 * was reversed, and one that was refunded (per NearPay's webhook docs, a refund is NOT a separate event —
 * it is the SAME "Transaction Approved" payload with is_refunded: true — so it needs its own terminal
 * local status, distinct from "paid", to be recorded correctly). Also adds a small diagnostic column for
 * the "flag for investigation, never silently treat as paid" case (an amount/currency mismatch) required
 * by the verification rules — nothing before this had anywhere to record that.
 *
 * Purely additive: existing rows and their `pending`/`paid`/`failed` values are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE direct_appointment_payments MODIFY COLUMN status ENUM('pending', 'paid', 'failed', 'reversed', 'refunded') NOT NULL DEFAULT 'pending'");

        if (!\Illuminate\Support\Facades\Schema::hasColumn('direct_appointment_payments', 'verification_note')) {
            \Illuminate\Support\Facades\Schema::table('direct_appointment_payments', function (\Illuminate\Database\Schema\Blueprint $table) {
                // Set only when server-side verification found something that could not be safely trusted
                // (e.g. a verified amount/currency mismatch) — the payment's status is left unchanged, and
                // this records WHY, for manual investigation. Never set for a routine pending/paid/failed outcome.
                $table->text('verification_note')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE direct_appointment_payments MODIFY COLUMN status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending'");

        if (\Illuminate\Support\Facades\Schema::hasColumn('direct_appointment_payments', 'verification_note')) {
            \Illuminate\Support\Facades\Schema::table('direct_appointment_payments', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn('verification_note');
            });
        }
    }
};
