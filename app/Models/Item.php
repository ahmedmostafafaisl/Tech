<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'warehouse_id',
        'name',
        'description',
        'serial',
        'code',
        'image',
        'price',
        'quantity',
        'warranty',
        'warranty_period',
        'rec_id',
        'item_number',
        'site_id',
        'location_id',
        'dy_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    // installation item

    public function appointment()
    {
        return $this->hasOne(AppointmentItem::class, 'item_id');
    }

    // emergency item
    public function emergencyAppointmentItems()
    {
        return $this->hasOne(EmergencyMainItem::class, 'item_id');
    }

    // emergency item
    public function periodicAppointmentItems()
    {
        return $this->hasOne(PeriodicMainItem::class, 'item_id');
    }

    // user stock item
    public function userStockItems()
    {
        return $this->hasMany(UserStockItem::class, 'item_id');
    }
}
