<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationImage extends Model
{
    use HasFactory;

    protected $fillable = ['cancellation_reason_id', 'image'];

    public function reason()
    {
        return $this->belongsTo(CancellationReason::class, 'cancellation_reason_id');
    }
}
