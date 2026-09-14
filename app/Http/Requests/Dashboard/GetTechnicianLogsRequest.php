<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class GetTechnicianLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currentPage' => 'nullable|integer|min:1',
            'per_page'    => 'nullable|integer|min:1|max:1000',
        ];
    }
}
