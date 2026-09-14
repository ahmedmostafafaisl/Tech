<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinResetRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tech_id',
        'status', // pending, approved, rejected
        'reason',
    ];

    public function tech()
    {
        return $this->belongsTo(User::class);
    }
}
