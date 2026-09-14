<?php

namespace App\Http\Resources\AppointmentFormsAdmin;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreEmergencyTreeWithFieldsResponse extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'message' => 'Emergency tree created with solution fields.',
            'data'    => new EmergencyTreeResource($this->resource),
        ];
    }
}
