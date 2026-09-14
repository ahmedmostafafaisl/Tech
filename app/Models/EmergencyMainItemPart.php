<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyMainItemPart extends Model
{
    use HasFactory;
    protected $fillable = [
        'appointment_id',
        'emergency_main_item_id',
        'part_id',
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
        'discount_value',
        'discount_type',
        'total_price',
        'status',
        'payment_type',
        'payment_status'

    ];
    protected $casts = [
        'check_list' => 'array',
        'issues_reported_from_client' => 'array',
        'valid_warranty' => 'boolean',
        'paid_service' => 'boolean',
        'missing' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(EmergencyMainItem::class, 'emergency_main_item_id');
    }
}
