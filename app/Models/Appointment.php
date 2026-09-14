<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'technician_id',
        'address_id',
        'type',
        'phone',
        'address',
        'latitude',
        'longitude',
        'branch',
        'sector',
        'appointment_date',
        'appointment_time',
        'total_price',
        'paid',
        'collect',
        'service_type',
        'cancel_notes',
        'reschedule_notes',
        'status',
        'whats_app',
        'alternative_number',
        'billing_status',
        'power_socket',
        'hold_reason',
        'sub_total_price',
        'discount',
        'discount_type',
        'discount_value',
        'maintenance_type',
        'complete_otp',
        'from',
        'to',
        'sales_order_id',
        'rec_id',
        'type_rec_id',
        'customer_confirmation_status',
        'description_common_issues',
        'item_common_issues',
        'order_notes',
        'warranty_status',
        'notes_optional',
        'book_id',
        'total_price',
        'collect',
        'tech_status',
        'confirmation_status',
        'dy_completed',
        'dy365_status',
        'dy_response',
        'v2_flag',

    ];
    protected $casts = [
        'total_price' => 'float',
        'collect' => 'float',
    ];
    protected $attributes = [
        'dy_response' => '{"status":"pending"}',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($appointment) {
            $appointment->appointment_num = self::generateUniqueAppointmentNumber();
        });
    }

    protected static function generateUniqueAppointmentNumber()
    {
        $number = 'APP-L-' . time() . '-' . rand(1000, 9999);

        // Ensure uniqueness
        if (self::where('appointment_num', $number)->exists()) {
            return self::generateUniqueAppointmentNumber();
        }

        return $number;
    }


    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function statusChanges()
    {
        return $this->hasMany(AppointmentStatusChange::class, 'appointment_id');
    }

    public function items()
    {
        return $this->hasMany(AppointmentItem::class, 'appointment_id');
    }
    public function parts()
    {
        return $this->hasMany(AppointmentPart::class, 'appointment_id');
    }
    public function emergencyItems()
    {
        return $this->hasMany(EmergencyMainItem::class, 'appointment_id');
    }

    public function periodicItems()
    {
        return $this->hasMany(PeriodicMainItem::class, 'appointment_id');
    }

    public function cancellation()
    {
        return $this->hasMany(CancellationReason::class);
    }
    public function reschedule()
    {
        return $this->hasMany(RescheduleReason::class);
    }

    public function complete()
    {
        return $this->hasMany(CompleteNote::class);
    }
    public function notes()
    {
        return $this->hasMany(CompleteNote::class, 'appointment_id');
    }



    public function payments()
    {
        return $this->hasMany(AppointmentPayment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
    public function appAddress()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function lines()
    {
        return $this->hasMany(AppointmentLine::class);
    }

    public function changeStatusRequests()
    {
        return $this->hasMany(AppointmentChangeStatusRequest::class, 'appointment_id');
    }
    public function latestChangeStatusRequest()
    {
        return $this->hasOne(AppointmentChangeStatusRequest::class)->latestOfMany();
    }
}
