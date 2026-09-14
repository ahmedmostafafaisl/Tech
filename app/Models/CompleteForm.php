<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompleteForm extends Model
{
    use HasFactory;


    protected $table = 'complete_forms';

    protected $fillable = [
        'appointment_id',
        'sales_order_id',
        'book_id',
        'service_name',
        'notes',
        'home_salt',
        'home_salt_image',
        'device_salt',
        'device_salt_image',
        'carbon_depletion',
        'sink_cleaning',
        'drain_connection',
        'carbon_depletion_image',
        'sink_cleaning_image',
        'drain_connection_image',
        'problem',
        'solution',
        'additional_image',
    ];

    protected $casts = [
        'appointment_id'      => 'integer',
        'home_salt'           => 'integer',
        'device_salt'         => 'integer',
        'carbon_depletion'    => 'boolean',
        'sink_cleaning'       => 'boolean',
        'drain_connection'    => 'boolean',
        'additional_image' => 'array',
    ];


    public function appointment()
    {
        return $this->belongsTo(DirectAppointment::class);
    }
}
