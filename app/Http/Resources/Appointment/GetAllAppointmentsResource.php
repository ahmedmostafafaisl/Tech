<?php

namespace App\Http\Resources\Appointment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetAllAppointmentsResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tech' => $this->technician_id,
            'appointment_num' => $this->appointment_num,
            'customer_name' => optional($this->customer)->username,
            'technician' => optional($this->technician)->username,
            'maintenance_type' => $this->type,
            'phone' => $this->phone,
            'status' => $this->status,
            'billing_status' => $this->billing_status,
            'payment_status' => $this->payment_status,
            'dy365_status' => $this->dy365_status,
            'dy_response' => $this->dy_response,
            'discount_value' => $this->discount_value,
            'total_price' => $this->total_price,
            'appointment_date' => $this->appointment_date,
            'appointment_time' => $this->appointment_time,
            'from' => $this->from,
            'to' => $this->to,
            'date_time' => $this->appointment_date . 'T' . $this->appointment_time . ':00Z',

        ];
    }
}
