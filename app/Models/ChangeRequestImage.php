<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequestImage extends Model
{
    use HasFactory;
    protected $fillable = ['change_request_id', 'image'];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }
}
