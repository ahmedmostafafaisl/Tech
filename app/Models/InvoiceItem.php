<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'item_id',
        'item_name',
        'item_price',
        'item_qty',
        'item_sub_total',
        'item_sub_total_price',
        'item_discount',
        'discount_type',
        'discount_value',
        'item_total_price',
    ];




    // Relationships
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
