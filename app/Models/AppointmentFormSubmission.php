<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppointmentFormSubmission extends Model
{
    protected $fillable = [
        'sales_order_id',
        'book_id',
        'appointment_id',
        'appointment_type_id',
        'root_option_id',
        'problem_option_id',
        'solution_option_id',
        'submitted_by',
    ];

    public function type()
    {
        return $this->belongsTo(AppointmentType::class, 'appointment_type_id');
    }

    public function rootOption()
    {
        return $this->belongsTo(AppointmentTypeOption::class, 'root_option_id');
    }

    public function problemOption()
    {
        return $this->belongsTo(AppointmentTypeOption::class, 'problem_option_id');
    }

    public function solutionOption()
    {
        return $this->belongsTo(AppointmentTypeOption::class, 'solution_option_id');
    }

    public function values()
    {
        return $this->hasMany(AppointmentFormSubmissionValue::class, 'submission_id');
    }

    public function appointment()
    {
        return $this->belongsTo(DirectAppointment::class, 'appointment_id', 'id');
    }
}
