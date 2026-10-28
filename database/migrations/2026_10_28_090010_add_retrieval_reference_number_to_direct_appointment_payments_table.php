<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NFC (tap-to-pay via NearPay) carries a card-network-level identifier — the Retrieval Reference Number —
 * that neither the existing TABI/TAMARA/E-Commerce flows nor the original NEARPAY (device-SDK) flow ever
 * had anywhere to record. Reported by the device after the tap completes; kept as a plain nullable string
 * since it is NearPay's/the card network's own value, not something generated or validated locally.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('direct_appointment_payments', 'retrieval_reference_number')) {
            Schema::table('direct_appointment_payments', function (Blueprint $table) {
                $table->string('retrieval_reference_number')->nullable()->after('payment_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('direct_appointment_payments', 'retrieval_reference_number')) {
            Schema::table('direct_appointment_payments', function (Blueprint $table) {
                $table->dropColumn('retrieval_reference_number');
            });
        }
    }
};
