<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentChangeStatusRequest extends Model
{
    use HasFactory;


    protected $fillable = [
        'rec_id',
        'appointment_id',
        'technician_id',
        'customer_id',
        'sales_order_id',
        'status',
        'type',
        'description',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
