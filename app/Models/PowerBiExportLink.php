<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowerBiExportLink extends Model
{
    protected $fillable = [
        'month',
        'download_url',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];
}
