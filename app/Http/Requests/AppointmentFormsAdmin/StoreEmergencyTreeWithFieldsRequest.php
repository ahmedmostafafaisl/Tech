<?php

namespace App\Http\Requests\AppointmentFormsAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmergencyTreeWithFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // أو ضع صلاحيات sanctum/abilities هنا
    }

    public function rules(): array
    {
        return [
            'devices' => ['required', 'array', 'min:1'],

            'devices.*.device' => ['required', 'array'],
            'devices.*.device.label_ar'   => ['required', 'string', 'max:255'],
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
            'devices.*.problems.*.solutions.*.fields.*.field_type'  => [
                'required_with:devices.*.problems.*.solutions.*.fields',
                Rule::in(['text', 'textarea', 'number', 'boolean', 'select', 'image', 'file', 'datetime']),
            ],
            'devices.*.problems.*.solutions.*.fields.*.is_required' => ['nullable', 'boolean'],
            'devices.*.problems.*.solutions.*.fields.*.sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
