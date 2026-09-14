<?php

namespace App\Http\Requests\CompleteForm;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompleteFormRequest extends FormRequest
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

            'sales_order_id' => 'required|string|max:255',
            'book_id'        => 'required|string|max:255',
            'service_name'   => 'nullable|string|max:255',
            'notes'          => 'nullable|string',

            'home_salt'       => 'required|integer',
            'home_salt_image'          => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'device_salt'       => 'required|integer',
            'device_salt_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',

            'carbon_depletion' => 'required|boolean',
            'sink_cleaning'    => 'required|boolean',
            'drain_connection' => 'required|boolean',

            'carbon_depletion_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'sink_cleaning_image'    => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'drain_connection_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'additional_image'         => 'nullable|array|max:5',
            'additional_image.*'       => 'file|mimes:jpg,jpeg,png,webp|max:5120',

            'problem'  => 'nullable|string',
            'solution' => 'nullable|string',
        ];
    }
}
