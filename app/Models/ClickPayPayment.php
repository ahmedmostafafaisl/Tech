<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClickPayPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'appointment_id',
        'sales_line_id',
        'phone_number',
        'reference_id',
        'order_number',
        'checkout_url',
        'status',
        'amount',
    ];
}
