<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduleReason extends Model
{
    use HasFactory;
    protected $fillable = ['appointment_id', 'reason'];
    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function images()
    {
        return $this->hasMany(RescheduleImage::class);
    }
}
