<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferOrderLines extends Model
{
    use HasFactory;
    protected $table = 'transfer_order_lines';
    protected $fillable = [
        'transfer_order_id',
        'item_number',
        'item_rec_id',
        'quantity',
        'requested_quantity',
        'transferred_quantity',
        'type'
    ];

    public function transferOrder()
    {
        return $this->belongsTo(TransferOrder::class);
    }


    // In TransferOrderLine.php
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function part()
    {
        return $this->belongsTo(Part::class, 'part_id');
    }
}
