<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TabbyPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'reference_id',
        'appointment_id',
        'session_url',
        'status',
        'payment_id',
        'amount',
        'sales_line_id',
        'phone_number',
    ];
}
