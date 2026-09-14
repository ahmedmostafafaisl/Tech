<?php

namespace App\Http\Resources\Emergency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyMainItemPartResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'part_id' => $this->part_id,
            'serial' => $this->serial,
            'floor' => $this->floor,
            'apart' => $this->apart,
            'room' => $this->room,
            'code' => $this->code,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'description' => $this->description,
            'image' => $this->image,
            'price' => $this->price,
            'sub_total_price' => $this->sub_total_price,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'payment_type' => $this->payment_type,
            'payment_status' => $this->payment_status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
