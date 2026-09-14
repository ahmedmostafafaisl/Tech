<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SingleUserAppointments extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_num' => $this->appointment_num,
            'customer_name' => optional($this->customer)->username,
            'customer_phone' => optional($this->customer)->phone,
            'technician_name' => optional($this->technician)->username,
            'maintenance_type' => $this->type,
            'date_time' => $this->appointment_date . 'T' . $this->appointment_time . ':00Z',
            'status' => $this->status,
        ];
    }
}
