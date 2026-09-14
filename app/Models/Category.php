<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_category_id',
        'name',
        'description'
    ];
    public function items()
    {
        return $this->hasMany(Item::class, 'category_id');
    }

    public function parts()
    {
        return $this->hasMany(Part::class, 'category_id');
    }
}
