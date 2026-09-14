<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\WarehouseTransferredItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WarehouseTransfer extends Model
{
    use HasFactory;

    protected $fillable = ['warehouse_id', 'tech_id', 'date', 'status', 'reference_id', 'type'];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warehouseRequest) {
            $warehouseRequest->reference_id = self::generateUniqueAppointmentNumber();
        });
    }

    protected static function generateUniqueAppointmentNumber()
    {
        $number = 'NAQI-' . time() . '-' . rand(1000, 9999);

        // Ensure uniqueness
        if (self::where('reference_id', $number)->exists()) {
            return self::generateUniqueAppointmentNumber();
        }

        return $number;
    }


    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'tech_id');
    }


    public function requestedItems()
    {
        return $this->hasMany(WarehouseRequestedItem::class);
    }

    public function transferredItems()
    {
        return $this->hasMany(WarehouseTransferredItem::class);
    }

    public function requestedParts()
    {
        return $this->hasMany(WarehouseRequestedPart::class);
    }

    public function transferredParts()
    {
        return $this->hasMany(WarehouseTransferredPart::class);
    }
}
