<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseRequestedPart extends Model
{
    use HasFactory;


    protected $fillable = ['warehouse_transfer_id', 'quantity', 'part_id'];

    public function warehouseTransfer()
    {
        return $this->belongsTo(WarehouseTransfer::class);
    }


    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
