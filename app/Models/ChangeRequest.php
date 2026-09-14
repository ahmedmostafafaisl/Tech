<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'sales_order_id',
        'tech_id',
        'request_type',
        'notes',
        'reason_rec_id',
        'reason',
        'book_id',
    ];

    protected $casts = [
        'request_type' => 'boolean',
    ];

    public function images()
    {
        return $this->hasMany(ChangeRequestImage::class);
    }

    public function additionalImages()
    {
        return $this->hasMany(ChangeRequestAdditionalImage::class);
    }

    // images
    public function callImages()
    {
        return $this->hasMany(ChangeRequestImage::class)->where('type', 'call');
    }

    public function chatImages()
    {
        return $this->hasMany(ChangeRequestImage::class)->where('type', 'chat');
    }

    public function additionalAllImages()
    {
        return $this->hasMany(ChangeRequestImage::class)->where('type', 'additional');
    }
}
