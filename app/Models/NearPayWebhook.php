<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NearPayWebhook extends Model
{
    // "NearPay" is one brand word, not two — without this, Eloquent's naming convention would look for
    // near_pay_webhooks instead of the migration's actual nearpay_webhooks (matching tabby_webhooks' style).
    protected $table = 'nearpay_webhooks';

    protected $fillable = [
        'transaction_id',
        'is_approved',
        'is_refunded',
        'is_reversed',
        'event_key',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'is_approved'  => 'boolean',
        'is_refunded'  => 'boolean',
        'is_reversed'  => 'boolean',
        'processed_at' => 'datetime',
    ];
}
