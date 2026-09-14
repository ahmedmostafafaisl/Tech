<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompleteNote extends Model
{
    use HasFactory;
    protected $fillable = [
        'appointment_id',
        'note',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function images()
    {
        return $this->hasMany(CompleteImage::class);
    }
}
