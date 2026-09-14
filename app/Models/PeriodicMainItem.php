<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodicMainItem extends Model
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
        'quantity',
        'description',
        'image',
        'price',
        'sub_total_price',
        'discount',
        'discount_type',
        'discount_value',
        'total_price',
        'status',
        'payment_type',
        'payment_status',
        'check_list',
        'valid_warranty',
        'paid_service',
        'missing',
        'maintenance_type',

    ];

    protected $casts = [
        'check_list' => 'array',
        'valid_warranty' => 'boolean',
        'paid_service' => 'boolean',
        'missing' => 'boolean',
    ];


    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function parts()
    {
        return $this->hasMany(PeriodicMainItemPart::class, 'periodic_main_item_id');
    }


    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
