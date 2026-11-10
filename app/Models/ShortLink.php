<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'url', 'hits', 'book_id', 'phone', 'sent', 'invoice_generated', 'last_error', 'message_id', 'delivery_status', 'delivery_error', 'type', 'evaluation_data'];

    protected $casts = [
        'sent' => 'boolean',
        'invoice_generated' => 'boolean',
        'evaluation_data' => 'array',
    ];
}
