<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Part extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'category_id',
        'name',
        'description',
        'serial',
        'code',
        'image',
        'price',
        'quantity',
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

    // user stock part
    public function userStockParts()
    {
        return $this->hasMany(UserStockPart::class, 'part_id');
    }
}
