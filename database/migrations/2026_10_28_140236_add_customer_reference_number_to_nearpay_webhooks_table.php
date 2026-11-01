<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Real Approved/Rejected/Reversed webhook deliveries all carry customer_reference_number — null in NearPay's
 * own documentation examples, but populated with whatever this app passed as the reference when the
 * transaction was initiated (this app's own DirectAppointmentPayment.reference_id), once a real device
 * actually reports it. This is a far more direct way to find the local payment than the existing
 * transaction_id/payment_id match (which depends on confirm()/handleNfcResponse() having already claimed
 * it first) — see ProcessNearPayWebhook for how the two are now used together.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('nearpay_webhooks', 'customer_reference_number')) {
            Schema::table('nearpay_webhooks', function (Blueprint $table) {
                $table->string('customer_reference_number')->nullable()->after('transaction_id');
                $table->index('customer_reference_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('nearpay_webhooks', 'customer_reference_number')) {
            Schema::table('nearpay_webhooks', function (Blueprint $table) {
                $table->dropIndex(['customer_reference_number']);
                $table->dropColumn('customer_reference_number');
            });
        }
    }
};
