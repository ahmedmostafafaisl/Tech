<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A new, independent log table for the new invoice-via-WhatsApp cycle (SendInvoiceWhatsAppMessageJob /
 * InvoiceWhatsAppSenderService) — deliberately separate from short_links, which belongs to the existing,
 * untouched cycle (SendInvoiceLinkJob). One row per send attempt.
 *
 * status: this attempt's own outcome — 'pending' until the job finishes, then 'sent' (Meta accepted it) or
 * 'failed' (DY365 had no invoice yet, the API call itself failed, or Meta rejected it).
 *
 * message_id / delivery_status / delivery_error: the same pattern short_links, pre_appointment_messages and
 * order_leads already use — message_id is set right after a successful send, delivery_status/delivery_error
 * are filled in later, asynchronously, by the existing /WhatsApp/receive webhook
 * (WhatsAppController::applyDeliveryStatus()), which gets one more lookup added for this table — the webhook
 * itself is shared, unchanged infrastructure, not part of either cycle specifically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('book_id');
            $table->string('phone');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->string('message_id')->nullable();
            $table->string('delivery_status')->nullable();
            $table->text('delivery_error')->nullable();
            $table->text('error')->nullable();
            $table->longText('response')->nullable();
            $table->timestamps();

            $table->index('book_id');
            $table->index('message_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_whatsapp_messages');
    }
};
