<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Two properties of the invoice/appointment itself — not the evaluation link sent alongside it — surfaced
 * as their own top-level fields rather than only living inside evaluation_data/evaluation_type:
 *
 * invoice_link: the actual invoice PDF URL sent as this message's Document header
 *   (InvoiceWhatsAppSenderService::send()'s $pdfUrl).
 * order_type: this appointment's own order_type, read from the local DirectAppointment row — the same value
 *   evaluation_type already carries, but order_type stays populated regardless of invoice_evaluation_active
 *   or whether the evaluation link fetch itself succeeded, since it isn't actually evaluation data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_whatsapp_messages', 'invoice_link')) {
                $table->text('invoice_link')->nullable()->after('response');
                $table->string('order_type')->nullable()->after('invoice_link');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn(['invoice_link', 'order_type']);
        });
    }
};
