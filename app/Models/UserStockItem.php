<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStockItem extends Model
{
    use HasFactory;

    protected $fillable = ['user_stock_id', 'item_id', 'quantity', 'item_number'];

    public function stock()
    {
        return $this->belongsTo(UserStock::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
