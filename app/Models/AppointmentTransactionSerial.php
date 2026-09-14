<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentTransactionSerial extends Model
{
    protected $fillable = [
        'appointment_transaction_line_id',
        'sales_line_rec_id',
        'item_number',
        'serial',
    ];

    protected $casts = [
        'sales_line_rec_id'               => 'integer',
        'appointment_transaction_line_id' => 'integer',
    ];

    public function line(): BelongsTo
    {
        return $this->belongsTo(AppointmentTransactionLine::class, 'appointment_transaction_line_id');
    }
}
