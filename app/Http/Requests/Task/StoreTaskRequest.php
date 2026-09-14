<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'technician_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'description' => 'required|string',
            'status' => 'in:pending,completed,canceled',
            'priority' => 'in:low,medium,high',
            'due_date' => 'required|date',
            'note' => 'nullable|string',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ];
    }
}
