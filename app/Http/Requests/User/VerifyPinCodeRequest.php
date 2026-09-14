<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyPinCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_auth' => auth('api')->check(),
        ]);

        /*
        |--------------------------------------------------------------
        | Return 401 if user is not authenticated
        | and phone is not provided
        |--------------------------------------------------------------
        */
        if (! auth('api')->check() && ! $this->filled('phone')) {
            throw new HttpResponseException(
                response()->json([
                    'message' => 'Unauthenticated.',
                ], 401)
            );
        }
    }

    public function rules(): array
    {
        return [
            'pin_code'      => 'required|string|size:4',
            'phone'         => 'required_if:is_auth,false|exists:users,phone',
            'update_version' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'pin_code.required'       => 'The pin code field is required.',
            'pin_code.string'         => 'The pin code must be a string.',
            'pin_code.size'           => 'The pin code must be 4 digits.',

            'phone.required_if'       => 'The phone field is required when no authenticated user is present.',
            'phone.exists'            => 'The selected phone does not exist.',

            'update_version.required' => 'The update version field is required.',
            'update_version.integer'  => 'The update version must be an integer.',
        ];
    }
}
