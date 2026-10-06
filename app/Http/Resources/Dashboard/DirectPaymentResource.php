<?php

namespace App\Http\Resources\Dashboard;

use App\Helper\DashboardDates;
use App\Services\Payment\DirectPaymentGateways;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** One direct payment as the dashboard lists it. Dates are "Y-m-d H:i:s" in the application timezone, like the other dashboard lists. */
class DirectPaymentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'type'                  => DirectPaymentGateways::typeOf($this->payment_type),   // tabby | tamara | clickpay
            'payment_type'          => $this->payment_type,                                  // as stored (TABI / TAMARA / E-Commerce)
            'reference_id'          => $this->reference_id,
            'payment_id'            => $this->payment_id,
            'status'                => $this->status,
            'price'                 => (float) $this->price,
            'phone'                 => $this->phone,
            'sales_order_id'        => $this->sales_order_id,
            'book_id'               => $this->book_id,
            'direct_appointment_id' => $this->direct_appointment_id,
            'created_at'            => DashboardDates::format($this->created_at),
            'updated_at'            => DashboardDates::format($this->updated_at),
        ];
    }
}
