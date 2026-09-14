<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectAppointmentPayment extends Model
{
    use HasFactory;


    protected $fillable = [
        'direct_appointment_id',
        'sales_order_id',
        'book_id',
        'price',
        'status',
        'payment_type',
        'phone',
        'reference_id',
        'payment_id',
    ];

    public function directAppointment()
    {
        return $this->belongsTo(DirectAppointment::class);
    }
}
