<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStockPart extends Model
{
    use HasFactory;


    protected $fillable = ['user_stock_id', 'part_id', 'quantity', 'item_number'];

    public function stock()
    {
        return $this->belongsTo(UserStock::class);
    }

    public function part()
    {
        return $this->belongsTo(Part::class);
    }
}
