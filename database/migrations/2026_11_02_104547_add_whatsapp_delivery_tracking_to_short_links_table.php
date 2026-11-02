<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the same tracking pre_appointment_messages already has (see add_delivery_status_tracking) —
 * message_id is WhatsApp's own id for the template message sent in SendInvoiceLinkJob, set right after a
 * successful send; delivery_status/delivery_error are filled in later, asynchronously, by the same
 * /WhatsApp/receive webhook (WhatsAppController::applyDeliveryStatus()) that already updates
 * pre_appointment_messages and order_leads — this is a third model added to that same lookup chain, not a
 * separate mechanism.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('short_links', function (Blueprint $table) {
            if (! Schema::hasColumn('short_links', 'message_id')) {
                $table->string('message_id')->nullable()->after('last_error');
            }

            if (! Schema::hasColumn('short_links', 'delivery_status')) {
                $table->string('delivery_status')->nullable()->after('message_id');
            }

            if (! Schema::hasColumn('short_links', 'delivery_error')) {
                $table->text('delivery_error')->nullable()->after('delivery_status');
            }
        });

        if (! Schema::hasIndex('short_links', 'short_links_message_id_index')) {
            Schema::table('short_links', function (Blueprint $table) {
                $table->index('message_id');
            });
        }
    }

    public function down(): void
    {
        // The index must be dropped before the column itself — SQLite in particular fails to drop a column
        // that still has an index on it in the same batch.
        if (Schema::hasColumn('short_links', 'message_id')) {
            try {
                Schema::table('short_links', fn(Blueprint $table) => $table->dropIndex(['message_id']));
            } catch (\Throwable $e) {
                // no such index — fine, drop the column regardless
            }
        }

        Schema::table('short_links', function (Blueprint $table) {
            foreach (['message_id', 'delivery_status', 'delivery_error'] as $column) {
                if (Schema::hasColumn('short_links', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
