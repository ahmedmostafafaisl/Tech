<?php

namespace App\Http\Controllers\Api\User\RolesAndPermissions;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use App\Http\Requests\User\Roles\RoleRequest;
use App\Http\Resources\User\Roles\RoleResource;
use App\Repositories\Interfaces\RolePermissionInterface;
use App\Http\Requests\User\Permissions\PermissionRequest;
use App\Http\Resources\User\Permissions\PermissionResource;

class RolePermissionController extends Controller

{
    public function __construct(private RolePermissionInterface $repository) {}

    // Roles
    public function roles()
    {
        return ($this->repository->allRoles());
    }

    public function storeRole(RoleRequest $request)
    {
        return $role = $this->repository->createRole($request->validated());
    }

    public function updateRole(RoleRequest $request, $id)
    {
        return  $role = $this->repository->updateRole($id, $request->validated());
    }
    public function showRole($id)
    {
        return $this->repository->findRole($id);
    }

    public function deleteRole($id)
    {
        return  $this->repository->deleteRole($id);
    }



    public function usersByRole(string $roleName)
    {

        return   $users = $this->repository->getUsersByRole($roleName);
    }
    // Permissions
    public function permissions()
    {
        return ($this->repository->allPermissions());
    }

    public function storePermission(PermissionRequest $request)
    {
        return   $permission = $this->repository->createPermission($request->all());
    }

    public function updatePermission(PermissionRequest $request, $id)
    {
        return $permission = $this->repository->updatePermission($id, $request->all());
    }

    public function deletePermission($id)
    {
        return $this->repository->deletePermission($id);
    }
}
