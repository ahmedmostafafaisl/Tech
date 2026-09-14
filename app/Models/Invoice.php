<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;


    protected $fillable = [
        'appointment_id',
        'invoice_status',
        'sub_total',
        'discount',
        'discount_type',
        'discount_value',
        'vat',
        'total',
        'invoice_url_pdf',
    ];

    // Relationships
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
