<?php

namespace App\Services\AppointmentForm;

use App\Models\AppointmentType;
use App\Models\AppointmentTypeOption;
use App\Models\FormField;
use App\Models\OptionField;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentFormService
{

    public function upsertAppointmentType(array $data): AppointmentType
    {
        // required: code, name_ar, name_en
        return AppointmentType::updateOrCreate(
            ['code' => $data['code']],
            [
                'name_ar'   => $data['name_ar'],
                'name_en'   => $data['name_en'],
                'is_active' => $data['is_active'] ?? true,
            ]
        );
    }


    public function createRootOption(int $appointmentTypeId, array $data): AppointmentTypeOption
    {
        // required: label_ar
        return AppointmentTypeOption::create([
            'appointment_type_id' => $appointmentTypeId,
            'parent_id'           => null,
            'label_ar'            => $data['label_ar'],
            'label_en'            => $data['label_en'] ?? null,
            'sort_order'          => $data['sort_order'] ?? 0,
            'is_active'           => $data['is_active'] ?? true,
        ]);
    }


    public function createChildOption(int $appointmentTypeId, int $parentOptionId, array $data): AppointmentTypeOption
    {
        return AppointmentTypeOption::create([
            'appointment_type_id' => $appointmentTypeId,
            'parent_id'           => $parentOptionId,
            'label_ar'            => $data['label_ar'],
            'label_en'            => $data['label_en'] ?? null,
            'sort_order'          => $data['sort_order'] ?? 0,
            'is_active'           => $data['is_active'] ?? true,
        ]);
    }


    public function upsertField(array $data): FormField
    {
        return FormField::updateOrCreate(
            ['field_key' => $data['field_key']],
            [
                'label_ar'   => $data['label_ar'],
                'label_en'   => $data['label_en'] ?? null,
                'field_type' => $data['field_type'],
                'is_active'  => $data['is_active'] ?? true,
            ]
        );
    }


    public function setOptionFields(int $optionId, array $fields): void
    {
        DB::transaction(function () use ($optionId, $fields) {

            // soft "replace" behavior: disable existing then re-add
            OptionField::where('option_id', $optionId)->update(['is_active' => false]);

            foreach ($fields as $f) {
                $field = $this->upsertField([
                    'field_key'  => $f['field_key'],
                    'label_ar'   => $f['label_ar'],
                    'label_en'   => $f['label_en'] ?? null,
                    'field_type' => $f['field_type'],
                ]);

                OptionField::updateOrCreate(
                    ['option_id' => $optionId, 'field_id' => $field->id],
                    [
                        'is_required' => (bool)($f['is_required'] ?? false),
                        'sort_order'  => (int)($f['sort_order'] ?? 0),
                        'is_active'   => true,
                    ]
                );
            }
        });
    }


    public function assertEmergencyOptionBelongs(string $expectedLevel, AppointmentTypeOption $option): void
    {
        // expectedLevel: device|problem|solution
        // device => parent_id null
        // problem => parent_id not null and parent.parent_id null
        // solution => parent_id not null and parent.parent_id not null and parent.parent.parent_id null (3 levels)
        $parent = $option->parent;

        if ($expectedLevel === 'device') {
            if (!is_null($option->parent_id)) {
                throw ValidationException::withMessages(['option' => 'Expected device (root option).']);
            }
            return;
        }

        if ($expectedLevel === 'problem') {
            if (is_null($option->parent_id) || !$parent || !is_null($parent->parent_id)) {
                throw ValidationException::withMessages(['option' => 'Expected problem (child of device).']);
            }
            return;
        }

        if ($expectedLevel === 'solution') {
            if (is_null($option->parent_id) || !$parent || is_null($parent->parent_id)) {
                throw ValidationException::withMessages(['option' => 'Expected solution (child of problem).']);
            }
            // ensure 3-level max (optional strict)
            $grand = $parent->parent;
            if (!$grand || !is_null($grand->parent_id)) {
                throw ValidationException::withMessages(['option' => 'Invalid emergency hierarchy depth.']);
            }
            return;
        }

        throw ValidationException::withMessages(['expectedLevel' => 'Invalid expected level.']);
    }


    public function addPeriodicOrServiceOptionsWithAttributes(string $typeCode, array $payload): array
    {
        if (!in_array($typeCode, ['periodic', 'service'], true)) {
            throw ValidationException::withMessages(['typeCode' => 'typeCode must be periodic or service.']);
        }

        $type = AppointmentType::where('code', $typeCode)->firstOrFail();

        return DB::transaction(function () use ($type, $payload) {

            $ids = [];

            foreach ($payload as $opt) {
                if (empty($opt['label_ar'])) {
                    throw ValidationException::withMessages(['label_ar' => 'Option label_ar is required.']);
                }

                $option = AppointmentTypeOption::create([
                    'appointment_type_id' => $type->id,
                    'parent_id'           => null,
                    'label_ar'            => $opt['label_ar'],
                    'label_en'            => $opt['label_en'] ?? null,
                    'sort_order'          => (int)($opt['sort_order'] ?? 0),
                    'is_active'           => (bool)($opt['is_active'] ?? true),
                ]);

                $this->replaceOptionFields($option->id, $opt['fields'] ?? []);

                $ids[] = $option->id;
            }

            // ✅ رجّع الداتا من DB بعد الربط عشان pivot values تطلع صح
            return AppointmentTypeOption::query()
                ->whereIn('id', $ids)
                ->with(['fields']) // includes pivot is_required/sort_order
                ->orderBy('sort_order')
                ->get()
                ->all();
        });
    }


    public function addEmergencyTreeWithSolutionAttributes(array $payload): array
    {
        $type = AppointmentType::where('code', 'emergency')->firstOrFail();

        return DB::transaction(function () use ($type, $payload) {
            $result = [];

            foreach ($payload as $deviceNode) {
                $deviceData = $deviceNode['device'] ?? null;
                if (!$deviceData || empty($deviceData['label_ar'])) {
                    throw ValidationException::withMessages(['device.label_ar' => 'Device label_ar is required.']);
                }

                // Create device (root)
                $device = AppointmentTypeOption::create([
                    'appointment_type_id' => $type->id,
                    'parent_id'           => null,
                    'label_ar'            => $deviceData['label_ar'],
                    'label_en'            => $deviceData['label_en'] ?? null,
                    'sort_order'          => (int)($deviceData['sort_order'] ?? 0),
                    'is_active'           => (bool)($deviceData['is_active'] ?? true),
                ]);

                $problemsOut = [];

                foreach (($deviceNode['problems'] ?? []) as $problemNode) {
                    $problemData = $problemNode['problem'] ?? null;
                    if (!$problemData || empty($problemData['label_ar'])) {
                        throw ValidationException::withMessages(['problem.label_ar' => 'Problem label_ar is required.']);
                    }

                    // Create problem (child of device)
                    $problem = AppointmentTypeOption::create([
                        'appointment_type_id' => $type->id,
                        'parent_id'           => $device->id,
                        'label_ar'            => $problemData['label_ar'],
                        'label_en'            => $problemData['label_en'] ?? null,
                        'sort_order'          => (int)($problemData['sort_order'] ?? 0),
                        'is_active'           => (bool)($problemData['is_active'] ?? true),
                    ]);

                    $solutionsOut = [];

                    foreach (($problemNode['solutions'] ?? []) as $solutionNode) {
                        $solutionData = $solutionNode['solution'] ?? null;
                        if (!$solutionData || empty($solutionData['label_ar'])) {
                            throw ValidationException::withMessages(['solution.label_ar' => 'Solution label_ar is required.']);
                        }

                        // Create solution (child of problem)
                        $solution = AppointmentTypeOption::create([
                            'appointment_type_id' => $type->id,
                            'parent_id'           => $problem->id,
                            'label_ar'            => $solutionData['label_ar'],
                            'label_en'            => $solutionData['label_en'] ?? null,
                            'sort_order'          => (int)($solutionData['sort_order'] ?? 0),
                            'is_active'           => (bool)($solutionData['is_active'] ?? true),
                        ]);

                        // Attach attributes (fields) to solution
                        $this->replaceOptionFields($solution->id, $solutionNode['fields'] ?? []);

                        $solutionsOut[] = [
                            'solution' => $solution->fresh(),
                            'fields_count' => OptionField::where('option_id', $solution->id)->where('is_active', true)->count(),
                        ];
                    }

                    $problemsOut[] = [
                        'problem' => $problem->fresh(),
                        'solutions' => $solutionsOut,
                    ];
                }

                $result[] = [
                    'device' => $device->fresh(),
                    'problems' => $problemsOut,
                ];
            }

            return $result;
        });
    }

    private function replaceOptionFields(int $optionId, array $fields): void
    {
        // allow empty fields (some options might not need)
        OptionField::where('option_id', $optionId)->update(['is_active' => false]);

        foreach ($fields as $f) {
            if (empty($f['field_key']) || empty($f['label_ar']) || empty($f['field_type'])) {
                throw ValidationException::withMessages([
                    'fields' => 'Each field requires field_key, label_ar, field_type.'
                ]);
            }

            $field = FormField::updateOrCreate(
                ['field_key' => $f['field_key']],
                [
                    'label_ar'   => $f['label_ar'],
                    'label_en'   => $f['label_en'] ?? null,
                    'field_type' => $f['field_type'],
                    'is_active'  => true,
                ]
            );

            OptionField::updateOrCreate(
                ['option_id' => $optionId, 'field_id' => $field->id],
                [
                    'is_required' => (bool)($f['is_required'] ?? false),
                    'sort_order'  => (int)($f['sort_order'] ?? 0),
                    'is_active'   => true,
                ]
            );
        }
    }

    // for add new type with full data (options tree + fields) in one request
    public function createTypeWithData(array $payload): array
    {
        return DB::transaction(function () use ($payload) {

            $typeData  = $payload['type'];
            $structure = $payload['structure'];

            // ✅ تأكد code unique (لو عندك unique index في DB هيمسكها برضه)
            $exists = AppointmentType::where('code', $typeData['code'])->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'type.code' => 'This type code already exists.',
                ]);
            }

            // 1) Create Appointment Type
            $type = AppointmentType::create([
                'code'       => $typeData['code'],
                'name_ar'    => $typeData['name_ar'],
                'name_en'    => $typeData['name_en'] ?? null,
                'sort_order' => (int)($typeData['sort_order'] ?? 0),
                'is_active'  => (bool)($typeData['is_active'] ?? true),
            ]);

            // 2) Create data based on structure
            if ($structure === 'options') {
                $options = $this->addOptionsWithAttributesForTypeId($type->id, $payload['options']);

                return [
                    'type'      => $type->fresh(),
                    'structure' => 'options',
                    'options'   => $options, // AppointmentTypeOption models loaded with fields + pivot
                ];
            }

            if ($structure === 'emergency_tree') {
                $tree = $this->addEmergencyTreeForTypeId($type->id, $payload['devices']);

                return [
                    'type'      => $type->fresh(),
                    'structure' => 'emergency_tree',
                    'tree'      => $tree, // array tree (device->problem->solution->fields)
                ];
            }

            throw ValidationException::withMessages(['structure' => 'Invalid structure.']);
        });
    }


    private function addOptionsWithAttributesForTypeId(int $typeId, array $optionsPayload): array
    {
        $ids = [];

        foreach ($optionsPayload as $opt) {
            if (empty($opt['label_ar'])) {
                throw ValidationException::withMessages(['options.label_ar' => 'Option label_ar is required.']);
            }

            $option = AppointmentTypeOption::create([
                'appointment_type_id' => $typeId,
                'parent_id'           => null,
                'label_ar'            => $opt['label_ar'],
                'label_en'            => $opt['label_en'] ?? null,
                'sort_order'          => (int)($opt['sort_order'] ?? 0),
                'is_active'           => (bool)($opt['is_active'] ?? true),
            ]);

            $this->replaceOptionFields($option->id, $opt['fields'] ?? []);
            $ids[] = $option->id;
        }

        // ✅ load fields + pivot
        return AppointmentTypeOption::query()
            ->whereIn('id', $ids)
            ->with(['fields']) // لازم relation fields() موجودة وبتجيب pivot values
            ->orderBy('sort_order')
            ->get()
            ->all();
    }


    private function addEmergencyTreeForTypeId(int $typeId, array $devicesPayload): array
    {
        $resultTree = [];

        foreach ($devicesPayload as $deviceNode) {

            $deviceData = $deviceNode['device'];

            // 1) Create device (root option)
            $deviceOption = AppointmentTypeOption::create([
                'appointment_type_id' => $typeId,
                'parent_id'           => null,
                'label_ar'            => $deviceData['label_ar'],
                'label_en'            => $deviceData['label_en'] ?? null,
                'sort_order'          => (int)($deviceData['sort_order'] ?? 0),
                'is_active'           => (bool)($deviceData['is_active'] ?? true),
            ]);

            $problemsArr = [];

            foreach (($deviceNode['problems'] ?? []) as $problemNode) {

                $problemData = $problemNode['problem'];

                // 2) Create problem (child of device)
                $problemOption = AppointmentTypeOption::create([
                    'appointment_type_id' => $typeId,
                    'parent_id'           => $deviceOption->id,
                    'label_ar'            => $problemData['label_ar'],
                    'label_en'            => $problemData['label_en'] ?? null,
                    'sort_order'          => (int)($problemData['sort_order'] ?? 0),
                    'is_active'           => (bool)($problemData['is_active'] ?? true),
                ]);

                $solutionsArr = [];

                foreach (($problemNode['solutions'] ?? []) as $solutionNode) {

                    $solutionData = $solutionNode['solution'];

                    // 3) Create solution (child of problem)
                    $solutionOption = AppointmentTypeOption::create([
                        'appointment_type_id' => $typeId,
                        'parent_id'           => $problemOption->id,
                        'label_ar'            => $solutionData['label_ar'],
                        'label_en'            => $solutionData['label_en'] ?? null,
                        'sort_order'          => (int)($solutionData['sort_order'] ?? 0),
                        'is_active'           => (bool)($solutionData['is_active'] ?? true),
                    ]);

                    // 4) Attach fields to solution only
                    $this->replaceOptionFields($solutionOption->id, $solutionNode['fields'] ?? []);

                    // ✅ reload with fields + pivot
                    $solutionOption = AppointmentTypeOption::query()
                        ->where('id', $solutionOption->id)
                        ->with(['fields'])
                        ->first();

                    $solutionsArr[] = [
                        'solution' => $solutionOption,
                        'fields'   => $solutionOption?->fields ?? [],
                    ];
                }

                $problemsArr[] = [
                    'problem'   => $problemOption,
                    'solutions' => $solutionsArr,
                ];
            }

            $resultTree[] = [
                'device'   => $deviceOption,
                'problems' => $problemsArr,
            ];
        }

        return [
            'devices' => $resultTree,
        ];
    }
}
