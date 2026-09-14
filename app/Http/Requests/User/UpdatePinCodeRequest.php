<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePinCodeRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'current_pin' => 'required|string|min:4|max:4',
            'new_pin_code' => 'required|required|string|min:4|max:4',
        ];
    }
}
