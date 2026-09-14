<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreAppointmentMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'phone',
        'sales_order',
        'book_id',
        'appointment_id',
        'worker_id',
        'customer_id',
        'name',
        'type',
        'date',
        'items',
        'is_sent',
        'message_id',
        'delivery_status',
        'delivery_error',
        'response',
        'customer_response',
        'dy_response',
        'flag',
    ];
}
