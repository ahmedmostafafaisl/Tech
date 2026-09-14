<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskImage extends Model
{
    use HasFactory;
    protected $fillable = ['task_id', 'image'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
