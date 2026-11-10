<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ShortLink is now also used for evaluation links (EvaluationLinkResolver::shorten()), not only invoice
 * links — these two columns distinguish them on the same, already-existing table rather than adding a
 * second one. type: 'evaluation' when shorten() is called for an evaluation link, left null for an invoice
 * short link (unchanged, preserves existing rows' meaning). evaluation_data: the plain fields the real link
 * behind this short one was built from (OrderTypeId, book_id, tech_user_name, district) — the same shape
 * already tracked on invoice_whatsapp_messages.evaluation_data — kept here too since a standalone evaluation
 * send has no other tracking table of its own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            if (! Schema::hasColumn('short_links', 'type')) {
                $table->string('type')->nullable()->after('book_id');
                $table->json('evaluation_data')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            $table->dropColumn(['type', 'evaluation_data']);
        });
    }
};
