<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class PreAppointmentMessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->id,
            'phone'             => $this->phone,
            'sales_order'       => $this->sales_order,
            'book_id'           => $this->book_id,
            'appointment_id'    => $this->appointment_id,
            'worker_id'         => $this->worker_id,
            'customer_id'       => $this->customer_id,
            'name'              => $this->name,
            'type'              => $this->type,
            'date'              => $this->date,
            'items'             => $this->items,
            'is_sent'           => $this->is_sent,
            'response'          => $this->response,
            'dy_response'       => $this->dy_response,
            'flag'              => $this->flag,
            'customer_response' => $this->customer_response,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
