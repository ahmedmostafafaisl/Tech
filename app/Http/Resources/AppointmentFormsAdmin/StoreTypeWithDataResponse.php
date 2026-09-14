<?php

namespace App\Http\Resources\AppointmentFormsAdmin;

use Illuminate\Http\Resources\Json\JsonResource;

class StoreTypeWithDataResponse extends JsonResource
{
    public function toArray($request): array
    {
        $type = data_get($this, 'type');

        return [
            'message' => 'Appointment type created with full data.',
            'type' => [
                'id' => data_get($type, 'id'),
                'code' => data_get($type, 'code'),
                'name_ar' => data_get($type, 'name_ar'),
                'name_en' => data_get($type, 'name_en'),
                'sort_order' => (int) data_get($type, 'sort_order', 0),
                'is_active'  => (bool) data_get($type, 'is_active', true),
            ],
            'structure' => data_get($this, 'structure'),
            'options' => data_get($this, 'structure') === 'options'
                ? OptionWithFieldsResource::collection(collect(data_get($this, 'options', [])))
                : null,
            'tree' => data_get($this, 'structure') === 'emergency_tree'
                ? data_get($this, 'tree')
                : null,
        ];
    }
}
