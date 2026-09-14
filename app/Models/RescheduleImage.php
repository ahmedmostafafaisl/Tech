<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduleImage extends Model
{
    use HasFactory;
    protected $fillable = ['reschedule_reason_id', 'image'];

    public function reason()
    {
        return $this->belongsTo(RescheduleReason::class, 'reschedule_reason_id');
    }
}
