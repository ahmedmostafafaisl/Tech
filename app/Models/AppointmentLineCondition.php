<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentLineCondition extends Model
{
    use HasFactory;
    protected $fillable = [
        'appointment_line_id',
        'status',
        'right_side_have_issue',
        'right_side_issues',
        'right_side_issue_note',
        'left_side_have_issue',
        'left_side_issues',
        'left_side_issue_note',
        'front_side_have_issue',
        'front_side_issues',
        'front_side_issue_note',
        'back_side_have_issue',
        'back_side_issues',
        'back_side_issue_note',
        'top_side_have_issue',
        'top_side_issues',
        'top_side_issue_note',
    ];

    protected $casts = [
        'right_side_issues' => 'array',
        'left_side_issues' => 'array',
        'front_side_issues' => 'array',
        'back_side_issues' => 'array',
        'top_side_issues' => 'array',
    ];

    public function appointmentLine()
    {
        return $this->belongsTo(AppointmentLine::class, 'appointment_line_id');
    }
    public function images()
    {
        return $this->hasMany(AppointmentLineConditionImage::class, 'condition_id');
    }
}
