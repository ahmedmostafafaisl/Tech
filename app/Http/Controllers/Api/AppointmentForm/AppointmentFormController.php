<?php

namespace App\Http\Controllers\Api\AppointmentForm;

use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentFormsAdmin\AppointmentTypeOptionTreeResource;
use App\Models\AppointmentType;
use App\Models\AppointmentTypeOption;
use App\Models\OptionField;
use Illuminate\Http\JsonResponse;

class AppointmentFormController extends Controller
{

    /**
     * 1) جميع أنواع المواعيد
     * GET /v1/appointment-forms/types
     */
    public function types(): JsonResponse
    {
        $types = AppointmentType::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'code', 'name_ar', 'name_en']);

        return response()->json([
            'data' => $types,
        ]);
    }



    public function rootOptions(string $typeCode): JsonResponse
    {
        $type = AppointmentType::query()
            ->where('is_active', true)
            ->where('code', $typeCode)
            ->firstOrFail();

        $options = AppointmentTypeOption::query()
            ->where('appointment_type_id', $type->id)
            ->whereNull('parent_id')
            ->active()
            ->with([
                'fields',
                'children'                          => fn($q) => $q->active()->orderBy('sort_order'),
                'children.fields',
                'children.children'                 => fn($q) => $q->active()->orderBy('sort_order'),
                'children.children.fields',
                'children.children.children'        => fn($q) => $q->active()->orderBy('sort_order'),
                'children.children.children.fields',
            ])
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'type' => [
                'id'      => $type->id,
                'code'    => $type->code,
                'name_ar' => $type->name_ar,
                'name_en' => $type->name_en,
            ],
            'data' => AppointmentTypeOptionTreeResource::collection($options),
        ]);
    }



    public function next(int $optionId): JsonResponse
    {
        $option = AppointmentTypeOption::query()
            ->with(['appointmentType'])
            ->active()
            ->findOrFail($optionId);

        $typeCode = $option->appointmentType->code;

        // Emergency => next is children (problems)
        if ($typeCode === 'emergency') {
            $children = AppointmentTypeOption::query()
                ->where('parent_id', $option->id)
                ->active()
                ->orderBy('sort_order')
                ->get(['id', 'appointment_type_id', 'parent_id', 'label_ar', 'label_en', 'sort_order']);

            return response()->json([
                'mode' => 'children', // مشاكل
                'data' => $children,
            ]);
        }

        // Periodic/Service => next is fields
        $fields = $this->loadFieldsForOption($option->id);

        return response()->json([
            'mode' => 'fields',
            'data' => $fields,
        ]);
    }


    public function children(int $optionId): JsonResponse
    {
        $option = AppointmentTypeOption::query()
            ->with('appointmentType')
            ->active()
            ->findOrFail($optionId);

        $children = AppointmentTypeOption::query()
            ->where('parent_id', $option->id)
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'appointment_type_id', 'parent_id', 'label_ar', 'label_en', 'sort_order']);

        return response()->json([
            'data' => $children,
        ]);
    }


    public function fields(int $optionId): JsonResponse
    {
        // نتأكد option موجود
        AppointmentTypeOption::query()->active()->findOrFail($optionId);

        $fields = $this->loadFieldsForOption($optionId);

        return response()->json([
            'data' => $fields,
        ]);
    }

    /**
     * Helper: fields المطلوبة لأي option
     */
    private function loadFieldsForOption(int $optionId)
    {
        return OptionField::query()
            ->with(['field:id,field_key,label_ar,label_en,field_type'])
            ->where('option_id', $optionId)
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(function (OptionField $of) {
                return [
                    'field_id'    => $of->field_id,
                    'field_key'   => $of->field->field_key,
                    'label_ar'    => $of->field->label_ar,
                    'label_en'    => $of->field->label_en,
                    'field_type'  => $of->field->field_type,
                    'is_required' => (bool) $of->is_required,
                    'sort_order'  => (int) $of->sort_order,
                ];
            })
            ->values();
    }
}
