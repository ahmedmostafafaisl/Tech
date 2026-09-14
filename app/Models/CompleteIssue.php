<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompleteIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'sales_order_id',
        'book_id',
        'required_amount',
        'calculated_amount',
        'body',
        'error_message',
    ];
}
