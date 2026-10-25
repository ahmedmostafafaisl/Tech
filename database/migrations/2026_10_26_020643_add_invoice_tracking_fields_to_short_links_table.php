<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * phone: the short link is now saved immediately (SendInvoiceLinkJob), before DY365 even confirms an
     * invoice exists — the phone number has to live on the record itself so the daily retry job
     * (RetryPendingInvoiceLinksCommand) knows who to resend to later, since it isn't a job parameter at
     * that point anymore.
     *
     * invoice_generated: whether DY365 has confirmed an invoice exists for this book_id. false means "not
     * generated yet" — exactly the case the daily retry job looks for.
     *
     * last_error: a short, human-readable reason the link wasn't sent — covers both "invoice not generated
     * yet" and any other send failure (SMS/WhatsApp), for visibility without digging through log files.
     */
    public function up(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('book_id');
            $table->boolean('invoice_generated')->default(false)->after('sent');
            $table->string('last_error')->nullable()->after('invoice_generated');

            $table->index('invoice_generated');
        });
    }

    public function down(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->dropIndex(['invoice_generated']);

            $table->dropColumn(['phone', 'invoice_generated', 'last_error']);
        });
    }
};
