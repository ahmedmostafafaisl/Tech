<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'description_en',
        'description_ar',
        'action_date',
        'body',
        'response',
    ];

    protected $casts = [
        'body' => 'array',
        'response' => 'json',
        'action_date' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
