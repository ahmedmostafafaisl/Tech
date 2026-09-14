<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChangeRequestReason extends Model
{
    protected $fillable = [
        'reason_rec_id',
        'reason_type',
        'reason',
        'title_ar',
        'title_en',
    ];
}
