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
        'installment_status',
        'total_amount_sum',
        'dy_required_amount',
        'used_balance',
        'paid_amount',
        'related_book_id',
        'customer_id',
    ];
    protected $casts = [
        'discount' => 'float',
    ];

    // Always included in this model's JSON — not something a caller has to think to request.
    protected $appends = ['related_appointment'];

    /**
     * true only when this is an emergency-maintenance visit ("صيانة طارئة" — see EvaluationLinkResolver for
     * the same order_type vocabulary) that includes at least one product under warranty. WarrantyStatus is
     * a string enum ('Yes'/'No'/'None'), never a real boolean — confirmed directly from its own validation
     * rule (NewCompleteAppointmentRequest: 'items.*.WarrantyStatus' => 'nullable|in:Yes,No,None') — so "is
     * true" here means the value 'Yes' specifically, not any truthy/non-empty value.
     */
    public function getRelatedAppointmentAttribute(): bool
    {
        return $this->order_type === 'صيانة طارئة'
            && $this->lines()->where('WarrantyStatus', 'Yes')->exists();
    }
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
