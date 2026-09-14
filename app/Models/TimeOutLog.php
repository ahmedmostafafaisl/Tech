<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOutLog extends Model
{
    use HasFactory;


    protected $fillable = [
        'tech_id',
        'route',
        'body',
        'time',
    ];
}
