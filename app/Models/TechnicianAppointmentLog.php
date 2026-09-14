<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianAppointmentLog extends Model
{
    protected $fillable = [
        'user_id',
        'tech_id',
        'book_id',
        'sales_order_id',
        'action',
        'status',
        'message',
        'error',
        'request_payload',
        'response_payload',
        'meta',
    ];

    protected $casts = [
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'meta'             => 'array',
    ];
}
