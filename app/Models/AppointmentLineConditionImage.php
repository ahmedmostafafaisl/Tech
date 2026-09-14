<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentLineConditionImage extends Model
{
    use HasFactory;
    protected $fillable = [
        'condition_id',
        'side',
        'path',
    ];

    public function condition()
    {
        return $this->belongsTo(AppointmentLineCondition::class, 'condition_id');
    }
}
