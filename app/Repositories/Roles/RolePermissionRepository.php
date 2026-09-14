<?php

namespace App\Repositories\Roles;

use App\Models\User;
use Illuminate\Support\Str;
use App\Helper\ApiResponseHelper;
use Spatie\Permission\Models\Role;
use App\Services\Logs\UserLogService;
use Spatie\Permission\Models\Permission;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\Roles\RoleResource;
use App\Http\Resources\User\Roles\AllRolesResource;
use App\Repositories\Interfaces\RolePermissionInterface;
use App\Http\Resources\User\Permissions\PermissionResource;


class RolePermissionRepository implements RolePermissionInterface
{
    // Roles
    use ApiResponseHelper;

    protected  $userLogService;
    public function __construct(UserLogService $userLogService)
    {
        $this->userLogService = $userLogService;
    }

    public function allRoles()
    {
        return $this->setCode(code: 200)->setData(AllRolesResource::collection(Role::all()))->setMessage('Success.')->send();
    }
    public function createRole(array $data)
    {
        // Ensure guard is sanctum
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        // Attach permissions if provided
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $permissions = collect($data['permissions'])->map(function ($permName) {
                return Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'web']
                );
            });

            $role->syncPermissions($permissions);
        }

        $this->userLogService->create(
            userId: auth()->id(),
            action: 'create_role',
            descriptionEn: 'Created a new role',
            descriptionAr: 'تم إنشاء دور جديد',
            body: [
                'role_id' => $role->id,
                'role_name' => $role->name,
            ],
        );

        return $this->setCode(200)->setData(new RoleResource($role))->setMessage('Success.')->send();
    }



    public function findRole(int $id)
    {
        return $this->setCode(code: 200)->setData(new RoleResource(Role::findOrFail($id)))->setMessage('Success.')->send();
    }
    public function updateRole(int $id, array $data)
    {
        $role = Role::findOrFail($id);

        $role->update([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        // Update permissions if provided
        if (!empty($data['permissions']) && is_array($data['permissions'])) {
            $permissions = collect($data['permissions'])->map(function ($permName) {
                return Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'web']
                );
            });

            $role->revokePermissionTo($role->permissions);
            foreach ($role->users as $user) {
                $user->permissions()->detach(); // Remove directly assigned permissions
            }

            $role->syncPermissions($permissions);
            foreach ($role->users as $user) {
                $user->syncPermissions($role->permissions);
            }
        }


        $this->userLogService->create(
            userId: auth()->id(),
            action: 'update_role',
            descriptionEn: 'Updated a role',
            descriptionAr: 'تم تحديث دور',
            body: [
                'role_id' => $role->id,
                'role_name' => $role->name,
            ],
        );

        return $this->setCode(200)->setData(new RoleResource($role))->setMessage('Success.')->send();
    }


    public function deleteRole(int $id)
    {
        $this->userLogService->create(
            userId: auth()->id(),
            action: 'delete_role',
            descriptionEn: 'Deleted a role',
            descriptionAr: 'تم حذف دور',
            body: [
                'role_id' => $id,

            ],
        );
        $role = $this->findRole($id);
        $role->delete();
        return $this->setCode(code: 200)->setData([])->setMessage('Role Deleted Successfully')->send();
    }

    public function getUsersByRole($roleId)
    {
        $role = Role::where('id', $roleId)->firstOrFail();
        $users = User::role($role->name)->get();
        return $this->setCode(code: 200)->setData(UserResource::collection($users))->setMessage('Success.')->send();
    }

    // Permissions


    public function allPermissions()
    {
        $permissions = Permission::all();

        $grouped = $permissions->groupBy(function ($permission) {
            // Group by the second word (e.g., "appointments" in "view appointments")
            return explode(' ', $permission->name, 2)[1] ?? 'other';
        })->map(function ($group) {
            return $group->pluck('name')->values();
        });

        return $this->setCode(200)
            ->setData($grouped)
            ->setMessage('Success.')
            ->send();
    }


    public function createPermission(array $data)
    {
        return $this->setCode(code: 200)->setData(new PermissionResource(Permission::create($data)))->setMessage('Success.')->send();
    }
    public function findPermission(int $id)
    {
        return $this->setCode(code: 200)->setData(new PermissionResource(Permission::findOrFail($id)))->setMessage('Success.')->send();
    }
    public function updatePermission(int $id, array $data)
    {
        $permission = $this->findPermission($id);
        $permission->update($data);
        return $this->setCode(code: 200)->setData(new PermissionResource($permission->update($data)))->setMessage('Success.')->send();
    }
    public function deletePermission(int $id)
    {
        $permission = $this->findPermission($id);
        $permission->delete();
        return $this->setCode(code: 200)->setData([])->setMessage('permission Deleted Successfully')->send();
    }
}
