<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompleteImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'complete_note_id',
        'image',
    ];

    public function note()
    {
        return $this->belongsTo(CompleteNote::class, 'complete_note_id');
    }
}
