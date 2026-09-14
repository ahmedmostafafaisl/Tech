<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'item_id',
        'serial',
        'floor',
        'apart',
        'room',
        'code',
        'name',
        'description',
        'image',
        'price',
        'quantity',
        'status',
        'payment_status',
        'payment_type',
        'check_list',
        'sub_total_price',
        'discount',
        'discount_type',
        'total_price',
        'discount_value',
    ];

    protected $casts = [
        'check_list' => 'array',
    ];


    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
