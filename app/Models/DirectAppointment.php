<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'total_price',
        'discount',
        'complete_flag',
        'status',
        'book_id',
        'dy_response',
        'tech_id',
        'complete_v2_calling',
        'required_amount',
        'notes',
        'collect',
        'dy_body',
        'dy_attachment_body',
        'dy_attachment_response',
        'dy_attachment_status',
        'customer_phone',
        'order_type',
        'installment_status'
    ];
    protected $casts = [
        'discount' => 'float',
    ];
    public function payments()
    {
        return $this->hasMany(DirectAppointmentPayment::class);
    }

    public function lines()
    {
        return $this->hasMany(DirectAppointmentLine::class, 'direct_appointment_id', 'id');
    }
    public function attachments()
    {
        return $this->hasMany(DirectAppointmentAttachment::class, 'direct_appointment_id', 'id');
    }

    // technician relation
    public function technician()
    {
        return $this->belongsTo(User::class, 'tech_id', 'tech_id');
    }

    public function completeForm()
    {
        return $this->hasOne(CompleteForm::class, 'appointment_id', 'id');
    }

    public function submissionForm()
    {
        return $this->hasOne(AppointmentFormSubmission::class, 'appointment_id', 'id');
    }
}
