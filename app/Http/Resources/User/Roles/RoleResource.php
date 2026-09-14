<?php

namespace App\Http\Resources\User\Roles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{

    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => $this->groupedPermissions(),
        ];
    }
    public function groupedPermissions()
    {
        return $this->permissions->groupBy(function ($permission) {
            return explode(' ', $permission->name, 2)[1] ?? 'other';
        })->map(function ($group) {
            return $group->pluck('name')->values();
        });
    }
}
