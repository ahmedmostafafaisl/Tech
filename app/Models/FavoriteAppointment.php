<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavoriteAppointment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sales_order_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
