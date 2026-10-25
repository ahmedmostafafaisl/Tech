<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortLink extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'url', 'hits', 'book_id', 'phone', 'sent', 'invoice_generated', 'last_error'];

    protected $casts = [
        'sent' => 'boolean',
        'invoice_generated' => 'boolean',
    ];
}
