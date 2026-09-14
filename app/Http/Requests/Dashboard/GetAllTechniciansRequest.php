<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class GetAllTechniciansRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'      => 'nullable|in:active,inactive',
            'search'      => 'nullable|string',
            'currentPage' => 'nullable|integer|min:1',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ];
    }
}
