<?php

namespace App\Http\Resources\AppointmentFormsAdmin;

use Illuminate\Http\Resources\Json\JsonResource;

class EmergencyProblemResource extends JsonResource
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

            // ✅ children هنا = solutions
            'solutions' => $this->whenLoaded('children', function () {
                return EmergencySolutionResource::collection($this->children);
            }, []),
        ];
    }
}
