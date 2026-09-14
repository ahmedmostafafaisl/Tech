<?php

namespace App\Http\Resources\AppointmentFormsAdmin;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentTypeOptionTreeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'appointment_type_id' => $this->appointment_type_id,
            'parent_id' => $this->parent_id,
            'label_ar' => $this->label_ar,
            'label_en' => $this->label_en,
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,

            // optional
            'fields' => $this->whenLoaded('fields', function () {
                return $this->fields->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'field_key' => $f->field_key,
                        'label_ar' => $f->label_ar,
                        'label_en' => $f->label_en,
                        'field_type' => $f->field_type,
                        'is_required' => (bool) data_get($f, 'pivot.is_required', false),
                        'sort_order'  => (int) data_get($f, 'pivot.sort_order', 0),
                    ];
                })->values();
            }, []),

            // ✅ recursive
            'children' => $this->whenLoaded('children', function () {
                return self::collection($this->children);
            }, []),
        ];
    }
}
