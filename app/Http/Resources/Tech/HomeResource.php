<?php

namespace App\Http\Resources\Tech;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        $missingItems = collect($this['missing_items'])->map(function ($item) {
            return array_merge($item, ['type' => 'item']);
        });

        $missingParts = collect($this['missing_parts'])->map(function ($part) {
            return array_merge($part, ['type' => 'part']);
        });

        return [
            'current_appointment' => $this['current_appointment'],
            'missing_inventory' => $missingItems->merge($missingParts)->values(),
            'tasks' => $this['tasks'],
            'cash_collect_today' => (string) $this['cash_collect_today'],
            'summary_appointment' => $this['summary_appointment'],
            'announcement' => $this['announcement'],
            'last_three_status_requests' => $this['last_three_status_requests'],
        ];
    }
}
