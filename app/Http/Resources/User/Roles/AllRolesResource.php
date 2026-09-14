<?php

namespace App\Http\Resources\User\Roles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllRolesResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->permissions->count(),
            'users_count' => $this->users_count ?? $this->users()->count(), // ✅ Fallback to count if not eager loaded

        ];
    }
}
