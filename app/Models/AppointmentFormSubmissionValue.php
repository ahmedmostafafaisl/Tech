<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentFormSubmissionValue extends Model
{
    protected $fillable = [
        'submission_id',
        'field_id',
        'value_text',
        'value_number',
        'value_boolean',
        'value_json',
    ];

    protected $casts = [
        'value_boolean' => 'boolean',
        'value_json' => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(AppointmentFormSubmission::class, 'submission_id');
    }

    public function field()
    {
        return $this->belongsTo(FormField::class, 'field_id');
    }
}
