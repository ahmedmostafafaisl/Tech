<?php

namespace App\Http\Controllers\Api\AppointmentForm;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentFormsAdmin\StoreEmergencyTreeWithFieldsRequest;
use App\Http\Requests\AppointmentFormsAdmin\StoreOptionsWithFieldsRequest;
use App\Http\Requests\AppointmentFormsAdmin\StoreTypeWithDataRequest;
use App\Http\Resources\AppointmentFormsAdmin\StoreEmergencyTreeWithFieldsResponse;
use App\Http\Resources\AppointmentFormsAdmin\StoreOptionsWithFieldsResponse;
use App\Http\Resources\AppointmentFormsAdmin\StoreTypeWithDataResponse;
use App\Models\AppointmentType;
use App\Models\AppointmentTypeOption;
use App\Services\AppointmentForm\AppointmentFormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentFormAdminController extends Controller
{
    public function __construct(private AppointmentFormService $service) {}

    /**
     * POST /v1/appointment-forms-admin/types
     * Body: { code, name_ar, name_en, is_active? }
     */
    public function storeType(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code'      => ['required', 'string', 'max:50'],
            'name_ar'   => ['required', 'string', 'max:255'],
            'name_en'   => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $type = $this->service->upsertAppointmentType($data);

        return response()->json([
            'message' => 'Appointment type saved.',
            'data'    => $type,
        ], 201);
    }

    /**
     * POST /v1/appointment-forms-admin/types/{typeCode}/options
     * Root option under appointment type (periodic/service/emergency device)
     * Body: { label_ar, label_en?, sort_order?, is_active? }
     */
    public function storeRootOption(string $typeCode, Request $request): JsonResponse
    {
        $type = AppointmentType::query()->where('code', $typeCode)->firstOrFail();

        $data = $request->validate([
            'label_ar'   => ['required', 'string', 'max:255'],
            'label_en'   => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $option = $this->service->createRootOption($type->id, $data);

        return response()->json([
            'message' => 'Root option created.',
            'data'    => $option,
        ], 201);
    }

    /**
     * POST /v1/appointment-forms-admin/options/{parentId}/children
     * Child option (emergency: problem under device OR solution under problem)
     * Body: { label_ar, label_en?, sort_order?, is_active? }
     */
    public function storeChildOption(int $parentId, Request $request): JsonResponse
    {
        $parent = AppointmentTypeOption::query()
            ->with('appointmentType')
            ->findOrFail($parentId);

        $data = $request->validate([
            'label_ar'   => ['required', 'string', 'max:255'],
            'label_en'   => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        // keep same appointment_type_id as parent
        $child = $this->service->createChildOption($parent->appointment_type_id, $parent->id, $data);

        return response()->json([
            'message' => 'Child option created.',
            'data'    => $child,
        ], 201);
    }

    /**
     * PUT /v1/appointment-forms-admin/options/{optionId}/fields
     * Replace required fields for an option (service option OR emergency solution)
     *
     * Body:
     * {
     *   "fields": [
     *     {
     *       "field_key":"salt_home_ppm",
     *       "label_ar":"نسبة الأملاح في مياه منزل العميل",
     *       "label_en":"Home water salinity",
     *       "field_type":"number",
     *       "is_required":true,
     *       "sort_order":10
     *     }
     *   ]
     * }
     */
    public function setFields(int $optionId, Request $request): JsonResponse
    {
        // ensure option exists
        $option = AppointmentTypeOption::query()->with('appointmentType')->findOrFail($optionId);

        $payload = $request->validate([
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.field_key' => ['required', 'string', 'max:100'],
            'fields.*.label_ar'  => ['required', 'string', 'max:255'],
            'fields.*.label_en'  => ['nullable', 'string', 'max:255'],
            'fields.*.field_type' => [
                'required',
                Rule::in(['text', 'textarea', 'number', 'boolean', 'select', 'image', 'file', 'datetime']),
            ],
            'fields.*.is_required' => ['nullable', 'boolean'],
            'fields.*.sort_order'  => ['nullable', 'integer', 'min:0'],
        ]);

        $this->service->setOptionFields($option->id, $payload['fields']);

        return response()->json([
            'message' => 'Fields updated for option.',
            'option'  => [
                'id' => $option->id,
                'appointment_type' => $option->appointmentType->code,
                'label_ar' => $option->label_ar,
            ],
        ]);
    }

    // bulk store

    public function storeOptionsWithFields(string $typeCode, StoreOptionsWithFieldsRequest $request): JsonResponse
    {
        if (!in_array($typeCode, ['periodic', 'service'], true)) {
            return response()->json(['message' => 'typeCode must be periodic or service.'], 422);
        }

        $created = $this->service->addPeriodicOrServiceOptionsWithAttributes($typeCode, $request->validated()['options']);

        return (new StoreOptionsWithFieldsResponse($created))
            ->response()
            ->setStatusCode(201);
    }

    public function storeEmergencyTreeWithFields(StoreEmergencyTreeWithFieldsRequest $request): JsonResponse
    {
        $result = $this->service->addEmergencyTreeWithSolutionAttributes($request->validated()['devices']);

        return (new StoreEmergencyTreeWithFieldsResponse($result))
            ->response()
            ->setStatusCode(201);
    }

    public function storeTypeWithData(StoreTypeWithDataRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->service->createTypeWithData($validated);

        return (new StoreTypeWithDataResponse($result))
            ->response()
            ->setStatusCode(201);
    }
}
