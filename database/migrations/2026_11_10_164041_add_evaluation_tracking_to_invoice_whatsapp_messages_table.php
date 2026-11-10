<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks the evaluation link sent alongside this invoice (see
 * InvoiceWhatsAppSenderService::buildEvaluationLinkForInvoice()), separate from this row's own
 * status/message_id/delivery_status (the invoice send itself) — a failed evaluation-link fetch never fails
 * or blocks the invoice send, so these two outcomes are tracked independently on the same row.
 *
 * evaluation_type: the order_type used to resolve the evaluation page.
 * evaluation_url: the full link actually sent (plain or with encrypted data, depending on
 *   invoice_evaluation_active) — never the decrypted payload itself.
 * evaluation_data: the plain fields the link's encrypted query param was built from (OrderTypeId, book_id,
 *   tech_user_name, district) — kept for operational visibility/debugging, not sensitive PII, and never the
 *   encryption key or raw ciphertext.
 * evaluation_status: 'sent' (link included, real or plain), 'skipped' (invoice_evaluation_active is off),
 *   or 'failed' (the data fetch itself errored, so a plain link was sent instead).
 * evaluation_error: set only when evaluation_status is 'failed'.
 *
 * retry_count / last_retried_at / retrying_at: support for the daily retry command — retrying_at is a short-
 * lived lock (cleared as soon as that attempt finishes, successfully or not) preventing two overlapping runs
 * of the retry command from both picking up and resending the same row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_whatsapp_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_whatsapp_messages', 'evaluation_type')) {
                $table->string('evaluation_type')->nullable()->after('response');
                $table->text('evaluation_url')->nullable()->after('evaluation_type');
                $table->json('evaluation_data')->nullable()->after('evaluation_url');
                $table->string('evaluation_status')->nullable()->after('evaluation_data');
                $table->text('evaluation_error')->nullable()->after('evaluation_status');
                $table->unsignedInteger('retry_count')->default(0)->after('evaluation_error');
                $table->timestamp('last_retried_at')->nullable()->after('retry_count');
                $table->timestamp('retrying_at')->nullable()->after('last_retried_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_whatsapp_messages', function (Blueprint $table) {
            $table->dropColumn([
                'evaluation_type',
                'evaluation_url',
                'evaluation_data',
                'evaluation_status',
                'evaluation_error',
                'retry_count',
                'last_retried_at',
                'retrying_at',
            ]);
        });
    }
};
