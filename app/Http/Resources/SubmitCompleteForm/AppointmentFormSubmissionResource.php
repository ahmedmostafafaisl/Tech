<?php

namespace App\Http\Resources\SubmitCompleteForm;


use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AppointmentFormSubmissionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sales_order_id' => $this->sales_order_id,
            'book_id' => $this->book_id,
            'appointment_id' => $this->appointment_id,

            'type' => $this->whenLoaded('type', fn() => [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name_ar' => $this->type->name_ar,
                'name_en' => $this->type->name_en,
            ]),

            'root_option' => $this->whenLoaded('rootOption'),
            'problem_option' => $this->whenLoaded('problemOption'),
            'solution_option' => $this->whenLoaded('solutionOption'),

            'values' => $this->whenLoaded('values', function () {
                return $this->values->map(function ($v) {
                    return [
                        'field' => [
                            'id' => $v->field->id,
                            'field_key' => $v->field->field_key,
                            'label_ar' => $v->field->label_ar,
                            'label_en' => $v->field->label_en,
                            'field_type' => $v->field->field_type,
                        ],
                        'value_text' => $v->value_text,
                        'value_number' => $v->value_number,
                        'value_boolean' => $v->value_boolean,
                        'value_json' => is_array($v->value_json)
                            ? array_map(fn($p) => Storage::disk('s3')->url($p), $v->value_json)
                            : $v->value_json,
                    ];
                })->values();
            }),
        ];
    }
}
