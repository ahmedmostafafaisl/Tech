<?php

namespace App\Http\Requests\CompleteForm;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompleteFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            // 'appointment_id' => 'nullable|integer',
            'sales_order_id' => 'nullable|string|max:255',
            'book_id'        => 'nullable|string|max:255',
            'service_name'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string',

            'home_salt'       => 'nullable|integer',
            'home_salt_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'device_salt'       => 'nullable|integer',
            'device_salt_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',

            'carbon_depletion' => 'nullable|boolean',
            'sink_cleaning'    => 'nullable|boolean',
            'drain_connection' => 'nullable|boolean',

            'carbon_depletion_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'sink_cleaning_image'    => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'drain_connection_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
            'additional_image'       => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',

            'problem'  => 'nullable|string',
            'solution' => 'nullable|string',
        ];
    }
}
