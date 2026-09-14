<?php

namespace App\Http\Resources\Address;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'city' => $this->city,
            'district' => $this->district,
            'branch' => $this->branch,
            'sector' => $this->sector,
            'status' => $this->status,
            'type' => $this->type,
            'name' => $this->name,
            'lat' => $this->lat,
            'long' => $this->long,
            'location_note' => $this->location_note,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
