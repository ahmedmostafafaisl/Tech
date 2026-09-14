<?php

namespace App\Http\Resources\AppointmentFormsAdmin;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreOptionsWithFieldsResponse extends JsonResource
{
    public function toArray($request): array
    {
        $items = collect($this->resource);

        return [
            'message' => 'Options created with fields.',
            'count'   => $items->count(),
            'data'    => OptionWithFieldsResource::collection($items),
        ];
    }
}
