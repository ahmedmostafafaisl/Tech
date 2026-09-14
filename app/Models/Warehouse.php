<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'rec_id', 'invent_location_id', 'type'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function parts()
    {
        return $this->hasMany(Part::class);
    }

    public function warehouseTransfers()
    {
        return $this->hasMany(WarehouseTransfer::class);
    }

    public function transferOrders()
    {
        return $this->hasMany(TransferOrder::class);
    }

    // user
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_warehouses', 'warehouse_id', 'user_id');
    }
}
