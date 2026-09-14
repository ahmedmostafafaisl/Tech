<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentStatusChange extends Model
{
    use HasFactory;

    protected $fillable = ['appointment_id', 'status', 'reason'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function images()
    {
        return $this->hasMany(AppointmentStatusImage::class, 'status_change_id');
    }
}
