<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AlinmaPay rejects a second pay-request that reuses the same order.orderId as an earlier, still-open one
 * — "A transaction is already in progress" — confirmed in production across three separate retries of the
 * same DirectAppointmentPayment row, all carrying the same reference_id as order.orderId every time.
 * reference_id has to stay stable (it's what the callback and the duplicate-payment check key off), so this
 * column holds the actual, freshly-generated order.orderId AlinmaPayService::createInvoiceNew() sends on
 * each individual attempt — a new value every retry, never reused — and is what
 * AlinmaPayPaymentSyncService::syncPayment() queries AlinmaPay with afterward, since that's the value
 * AlinmaPay's own records are actually keyed by.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('direct_appointment_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('direct_appointment_payments', 'gateway_order_id')) {
                $table->string('gateway_order_id')->nullable()->after('reference_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('direct_appointment_payments', function (Blueprint $table) {
            $table->dropColumn('gateway_order_id');
        });
    }
};
