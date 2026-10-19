<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DyPaymentLink extends Model
{
    use HasFactory;
    protected $fillable = [
        'payment_method',
        'payment_reference_id',
        'dy_reference_id',
        'status',
        'amount',
        'phone',
        'customer_account',
        'book_id',
        'dy_body',
        'dy_response',
        'checkout_url',
        'payment_id',
    ];
    protected $casts = [
        'amount' => 'decimal:2',
    ];
    public function lines()
    {
        return $this->hasMany(DyPaymentLine::class, 'dy_payment_id');
    }
}
