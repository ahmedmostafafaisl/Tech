<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_name' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            // 'role' => $this->role,
            'role'  => $this->roles?->pluck('name')->implode(', ') ?: '-',
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),

        ];
    }
}
