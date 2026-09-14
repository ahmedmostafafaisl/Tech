<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DyPaymentLine extends Model
{
    protected $table = 'dy_payment_lines';

    use HasFactory;
    protected $fillable = [
        'dy_payment_id',
        'name',
        'description',
        'quantity',
        'price',
        'discount',
        'rec_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'discount' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(DyPaymentLink::class, 'dy_payment_id');
    }
}
