<?php

namespace App\Models;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppointmentLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'item_rec_id',
        'item_number',
        'quantity',
        'price',
        'total_amount',
        'sales_line_id',
        'type',
        'line_id',
        'line_type',
        'is_paid',
        'discount',
        'discount_value',
        'discount_type',
        'sales_history_date',
        'warranty_status',
        'description_common_issues',
        'item_common_issues',
        'payment_method',
        'serial',
        'floor',
        'apart',
        'room',
        'code',
        'payment_status',
        'check_list',
        'issues_reported_from_client',
        'item_form_type',
        'item_form_status',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'line_id');
    }

    public function part()
    {
        return $this->belongsTo(Part::class, 'line_id');
    }



    public function conditions()
    {
        return $this->hasMany(AppointmentLineCondition::class, 'appointment_line_id');
    }
}
