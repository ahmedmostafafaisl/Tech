<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'part_id',
        'serial',
        'floor',
        'apart',
        'room',
        'code',
        'name',
        'description',
        'image',
        'price',
        'status',
        'payment_status',
        'quantity',
        'payment_type',
        'sub_total_price',
        'discount',
        'discount_type',
        'total_price',
        'discount_value',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }
}
