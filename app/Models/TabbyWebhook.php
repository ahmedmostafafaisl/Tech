<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TabbyWebhook extends Model
{
    protected $fillable = [
        'webhook_id',
        'payment_id',
        'reference_id',
        'status',
        'event_key',
        'is_test',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_test' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
