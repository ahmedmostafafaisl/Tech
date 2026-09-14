<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'tech_id'           => $this->tech_id,
            'username'          => $this->username,
            'phone'             => $this->phone,
            'status'            => $this->status,
            'warehouse_id'      => $this->warehouse_id,
            'personnel_number'  => $this->personnel_number,
            'created_at'        => $this->created_at,
        ];
    }
}
