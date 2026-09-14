<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectAppointmentLine extends Model
{
    use HasFactory;
    protected $fillable = [
        'direct_appointment_id',
        'sales_order_id',
        'SaleslineId',
        'ProductRecId',
        'ItemNumber',
        'ProductName',
        'OrderTypeRecId',
        'OrderTypeId',
        'IsPaid',
        'Quantity',
        'UnitPrice',
        'TotalAmount',
        'Discount',
        'ItemIdCommonIssue',
        'DescriptionCommonIssue',
        'SalesHistoryDate',
        'WarrantyStatus',
        'PaymentMethodRecId',
        'PaymentMethod',
        'PaymentReference',
        'max_quantity',
    ];
    protected $casts = [
        'IsPaid' => 'boolean',
        'Quantity' => 'decimal:2',
        'UnitPrice' => 'decimal:2',
        'TotalAmount' => 'decimal:2',
        'Discount' => 'decimal:2',
        'SalesHistoryDate' => 'datetime',
    ];


    protected $table = 'direct_appointment_lines';

    public  function directAppointment()
    {
        return $this->belongsTo(DirectAppointment::class, 'direct_appointment_id', 'direct_appointment_id');
    }
}
