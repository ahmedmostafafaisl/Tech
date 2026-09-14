<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentBundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_bundle_id',
        'item_number',
        'item_name',
        'quantity',
        'price',
        'order_type_rec_id',
        'warranty_status',
        'payment_method',
    ];

    public function bundle()
    {
        return $this->belongsTo(AppointmentBundle::class, 'appointment_bundle_id');
    }
}
