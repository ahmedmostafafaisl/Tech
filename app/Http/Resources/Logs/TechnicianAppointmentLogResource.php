<?php

namespace App\Http\Resources\Logs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianAppointmentLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'tech_id' => $this->tech_id,
            'book_id' => $this->book_id,
            'sales_order_id' => $this->sales_order_id,
            'action' => $this->action,
            'status' => $this->status,
            'message' => $this->message,
            'error' => $this->error,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
