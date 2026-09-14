<?php

namespace App\Http\Resources\Tech;

use Illuminate\Http\Request;
use App\Http\Resources\Item\ItemResource;
use Illuminate\Http\Resources\Json\JsonResource;

class TechResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'today_total_appointment_number' => $this['appointments_count'],
            "installation_items" =>  ItemResource::collection($this['installation_items']),
            "emergency_items" => ItemResource::collection($this["emergency_items"]),
            "cash_collect_yesterday" => ($this["cash_collect_yesterday"]),
        ];
    }
}
