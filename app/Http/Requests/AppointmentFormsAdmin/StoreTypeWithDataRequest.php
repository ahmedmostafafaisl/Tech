<?php

namespace App\Http\Requests\AppointmentFormsAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTypeWithDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fieldTypes = ['text', 'textarea', 'number', 'boolean', 'select', 'image', 'file', 'datetime'];

        return [
            'type' => ['required', 'array'],
            'type.code' => ['required', 'string', 'max:50'],
            'type.name_ar' => ['required', 'string', 'max:255'],
            'type.name_en' => ['nullable', 'string', 'max:255'],
            'type.sort_order' => ['nullable', 'integer', 'min:0'],
            'type.is_active' => ['nullable', 'boolean'],

            // structure defines which payload to expect
            'structure' => ['required', Rule::in(['options', 'emergency_tree'])],

            // ===== Structure A: options (periodic/service-like) =====
            'options' => ['required_if:structure,options', 'array', 'min:1'],

            'options.*.label_ar'   => ['required_if:structure,options', 'string', 'max:255'],
            'options.*.label_en'   => ['nullable', 'string', 'max:255'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*.is_active'  => ['nullable', 'boolean'],
            'options.*.fields' => ['nullable', 'array'],

            'options.*.fields.*.field_key'   => ['required_with:options.*.fields', 'string', 'max:100'],
            'options.*.fields.*.label_ar'    => ['required_with:options.*.fields', 'string', 'max:255'],
            'options.*.fields.*.label_en'    => ['nullable', 'string', 'max:255'],
            'options.*.fields.*.field_type'  => ['required_with:options.*.fields', Rule::in($fieldTypes)],
            'options.*.fields.*.is_required' => ['nullable', 'boolean'],
            'options.*.fields.*.sort_order'  => ['nullable', 'integer', 'min:0'],

            // ===== Structure B: emergency_tree =====
            'devices' => ['required_if:structure,emergency_tree', 'array', 'min:1'],

            'devices.*.device' => ['required_if:structure,emergency_tree', 'array'],
            'devices.*.device.label_ar'   => ['required_if:structure,emergency_tree', 'string', 'max:255'],
            'devices.*.device.label_en'   => ['nullable', 'string', 'max:255'],
            'devices.*.device.sort_order' => ['nullable', 'integer', 'min:0'],
            'devices.*.device.is_active'  => ['nullable', 'boolean'],

            'devices.*.problems' => ['nullable', 'array'],

            'devices.*.problems.*.problem' => ['required_with:devices.*.problems', 'array'],
            'devices.*.problems.*.problem.label_ar'   => ['required_with:devices.*.problems', 'string', 'max:255'],
            'devices.*.problems.*.problem.label_en'   => ['nullable', 'string', 'max:255'],
            'devices.*.problems.*.problem.sort_order' => ['nullable', 'integer', 'min:0'],
            'devices.*.problems.*.problem.is_active'  => ['nullable', 'boolean'],

            'devices.*.problems.*.solutions' => ['nullable', 'array'],

            'devices.*.problems.*.solutions.*.solution' => ['required_with:devices.*.problems.*.solutions', 'array'],
            'devices.*.problems.*.solutions.*.solution.label_ar'   => ['required_with:devices.*.problems.*.solutions', 'string', 'max:255'],
            'devices.*.problems.*.solutions.*.solution.label_en'   => ['nullable', 'string', 'max:255'],
            'devices.*.problems.*.solutions.*.solution.sort_order' => ['nullable', 'integer', 'min:0'],
            'devices.*.problems.*.solutions.*.solution.is_active'  => ['nullable', 'boolean'],

            'devices.*.problems.*.solutions.*.fields' => ['nullable', 'array'],
            'devices.*.problems.*.solutions.*.fields.*.field_key'   => ['required_with:devices.*.problems.*.solutions.*.fields', 'string', 'max:100'],
            'devices.*.problems.*.solutions.*.fields.*.label_ar'    => ['required_with:devices.*.problems.*.solutions.*.fields', 'string', 'max:255'],
            'devices.*.problems.*.solutions.*.fields.*.label_en'    => ['nullable', 'string', 'max:255'],
            'devices.*.problems.*.solutions.*.fields.*.field_type'  => ['required_with:devices.*.problems.*.solutions.*.fields', Rule::in($fieldTypes)],
            'devices.*.problems.*.solutions.*.fields.*.is_required' => ['nullable', 'boolean'],
            'devices.*.problems.*.solutions.*.fields.*.sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
