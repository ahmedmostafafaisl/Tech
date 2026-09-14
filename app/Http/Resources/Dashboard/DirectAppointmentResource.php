<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DirectAppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'sales_order_id'   => $this->sales_order_id,
            'book_id'          => $this->book_id,
            'total_price'      => $this->total_price,
            'discount'         => $this->discount,
            'required_amount'  => $this->required_amount,
            'status'           => $this->status,
            'complete_flag'    => $this->complete_flag,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at,
            'technician' => new TechnicianResource($this->whenLoaded('technician')),
        ];
    }
}
