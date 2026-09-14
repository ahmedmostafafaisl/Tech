<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'order_type',
        'sent',
        'book_id',
    ];

    protected $casts = [
        'sent' => 'boolean',
    ];
}
