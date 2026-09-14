<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentStatusImage extends Model
{
    use HasFactory;

    protected $fillable = ['status_change_id', 'image'];

    public function statusChange()
    {
        return $this->belongsTo(AppointmentStatusChange::class, 'status_change_id');
    }
}
