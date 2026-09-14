<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentPayment extends Model
{
    use HasFactory;
    protected $fillable = [
        'appointment_id',
        'payment_image',
        'payment_type',
        'payment_reference_id',
        'total_price',
        'status',
        'sales_line_id',
        'phone_number',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
