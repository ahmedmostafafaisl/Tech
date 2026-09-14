<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'technician_id' => 'sometimes|exists:users,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'status' => 'sometimes|in:pending,completed,canceled',
            'priority' => 'sometimes|in:low,medium,high',
            'due_date' => 'sometimes|date',
            'note' => 'nullable|string',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048' // Allow optional images update
        ];
    }
}
