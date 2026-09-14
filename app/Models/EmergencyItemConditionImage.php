<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyItemConditionImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'condition_id',
        'side',
        'path',
    ];


    public function condition()
    {
        return $this->belongsTo(EmergencyItemCondition::class, 'condition_id');
    }
}
