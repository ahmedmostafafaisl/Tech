<?php

namespace App\Repositories\Interfaces;

interface UserLogRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    // auth user logs
    public function getAuthUserLogs($userId);
}
