<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentAttachmentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // identifiers
            'sales_order_id' => 'required|string',
            'book_id'        => 'required|string',
            // notes (stored on appointment)
            'notes'          => 'nullable|string',
            // attachments (direct_appointment_attachments)
            'images'         => 'nullable|array',
            'images.*'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            // CompleteForm data
            'service_name'        => 'nullable|string|max:255',
            'home_salt'           => 'required|integer',
            'device_salt'         => 'required|integer',
            'carbon_depletion'    => 'required|boolean',
            'sink_cleaning'       => 'required|boolean',
            'drain_connection'    => 'required|boolean',
            'problem'             => 'nullable|string',
            'solution'            => 'nullable|string',
            // CompleteForm images
            'home_salt_image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'device_salt_image'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'carbon_depletion_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'sink_cleaning_image'    => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'drain_connection_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'additional_image'       => 'nullable|array',
            'additional_image.*'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'home_salt.required' => 'Home salt is required',
            'device_salt.required' => 'Device salt is required',
        ];
    }
}
