<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'name',
        'description',
        'status',
        'priority',
        'due_date',
        'type',
        'note',
        'icon'
    ];
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
    public function images()
    {
        return $this->hasMany(TaskImage::class);
    }
}
