<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentTransactionLine extends Model
{
    protected $fillable = [
        'appointment_transaction_id',
        'sales_line_rec_id',
        'item_number',
        'quantity',
        'order_type_rec_id',
        'warranty_status',
    ];

    protected $casts = [
        'sales_line_rec_id'          => 'integer',
        'quantity'                   => 'integer',
        'order_type_rec_id'          => 'integer',
        'appointment_transaction_id' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AppointmentTransaction::class, 'appointment_transaction_id');
    }

    public function serials(): HasMany
    {
        return $this->hasMany(AppointmentTransactionSerial::class);
    }
}
