<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DirectAppointmentAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'direct_appointment_id',
        'image',
    ];
    public function appointment()
    {
        return $this->belongsTo(DirectAppointment::class, 'direct_appointment_id', 'id');
    }

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        try {
            // Generate temporary signed URL valid for 100 hours
            return Storage::disk('s3')->temporaryUrl($this->image, now()->addHours(100));
        } catch (\Exception $e) {
            \Log::warning("Failed to generate temporary URL for {$this->image}: {$e->getMessage()}");
            return null;
        }
    }
}
