<?php

namespace App\Repositories\Interfaces;


interface RolePermissionInterface
{
    // Roles
    public function allRoles();
    public function createRole(array $data);
    public function findRole(int $id);
    public function updateRole(int $id, array $data);
    public function deleteRole(int $id);
    public function getUsersByRole($roleId);


    // Permissions
    public function allPermissions();
    public function createPermission(array $data);
    public function findPermission(int $id);
    public function updatePermission(int $id, array $data);
    public function deletePermission(int $id);
}
