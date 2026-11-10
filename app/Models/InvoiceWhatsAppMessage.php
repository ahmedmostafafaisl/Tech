<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per send attempt made by the new, independent invoice-via-WhatsApp cycle
 * (SendInvoiceWhatsAppMessageJob) — kept entirely separate from ShortLink, which belongs to the existing,
 * untouched cycle.
 */
class InvoiceWhatsAppMessage extends Model
{
    // Eloquent's default inference splits "WhatsApp" into "whats_app", giving "invoice_whats_app_messages" —
    // the actual migration (matching how "WhatsApp" is treated as one word everywhere else in this codebase,
    // e.g. nearpay_terminals for "NearPay") created invoice_whatsapp_messages, so this must be explicit.
    protected $table = 'invoice_whatsapp_messages';

    protected $fillable = [
        'book_id',
        'phone',
        'status',
        'message_id',
        'delivery_status',
        'delivery_error',
        'error',
        'response',
        'evaluation_type',
        'evaluation_url',
        'evaluation_data',
        'evaluation_status',
        'evaluation_error',
        'retry_count',
        'last_retried_at',
        'retrying_at',
    ];

    protected $casts = [
        'evaluation_data' => 'array',
        'last_retried_at' => 'datetime',
        'retrying_at'     => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
