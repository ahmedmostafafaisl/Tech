<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentBundle extends Model
{
    use HasFactory;


    protected $fillable = [
        'appointment_id',
        'book_id',
        'sales_order_id',
        'bundle_id',
        'bundle_name',
        'quantity',
        'order_type_rec_id',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(AppointmentBundleItem::class);
    }
}
