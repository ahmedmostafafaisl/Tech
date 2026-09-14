<?php

namespace App\Http\Resources\Emergency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Emergency\EmergencyMainItemPartResource;

class EmergencyMainItemResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'item_id' => $this->item_id,
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
            'check_list' => $this->check_list
                ? json_decode($this->check_list, true) ?? []
                : [],
            'valid_warranty' => $this->valid_warranty,
            'paid_service' => $this->paid_service,
            'missing' => $this->missing,
            'issues_reported_from_client' => $this->issues_reported_from_client
                ? json_decode($this->issues_reported_from_client, true) ?? []
                : [],
            'item_form_type' => $this->item_form_type,
            'item_form_status' => $this->item_form_status,
            'parts' => EmergencyMainItemPartResource::collection($this->whenLoaded('parts')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
