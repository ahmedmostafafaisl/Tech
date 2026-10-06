<?php

namespace App\Http\Requests\Settings;

use App\Services\Auth\DefaultOtp;
use App\Services\Auth\DefaultOtpAllowlist;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddDefaultOtpPhonesRequest extends FormRequest
{
    /** Access is enforced by the route middleware (super_admin only). */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'phones'   => ['required', 'array', 'min:1', 'max:' . DefaultOtpAllowlist::MAX_PHONES_PER_CALL],
            'phones.*' => [
                'required',
                'string',
                'max:20',
                'not_in:*',
                fn(string $attribute, mixed $value, \Closure $fail) => DefaultOtp::isValidPhoneToken(trim((string) $value))
                    ?: $fail('This is not a valid phone number (9-15 digits, optional +).'),
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => 'The given data was invalid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
