<?php

namespace App\Http\Requests\AppointmentFormsAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOptionsWithFieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'options' => ['required', 'array', 'min:1'],

            'options.*.label_ar'   => ['required', 'string', 'max:255'],
            'options.*.label_en'   => ['nullable', 'string', 'max:255'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'options.*.is_active'  => ['nullable', 'boolean'],

            'options.*.fields' => ['nullable', 'array'],

            'options.*.fields.*.field_key'   => ['required_with:options.*.fields', 'string', 'max:100'],
            'options.*.fields.*.label_ar'    => ['required_with:options.*.fields', 'string', 'max:255'],
            'options.*.fields.*.label_en'    => ['nullable', 'string', 'max:255'],
            'options.*.fields.*.field_type'  => [
                'required_with:options.*.fields',
                Rule::in(['text', 'textarea', 'number', 'boolean', 'select', 'image', 'file', 'datetime']),
            ],
            'options.*.fields.*.is_required' => ['nullable', 'boolean'],
            'options.*.fields.*.sort_order'  => ['nullable', 'integer', 'min:0'],
        ];
    }
}
