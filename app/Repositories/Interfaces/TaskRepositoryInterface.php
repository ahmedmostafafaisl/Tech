<?php

namespace App\Repositories\Interfaces;


interface TaskRepositoryInterface
{
    public function getAllTasks($perPage, $page, $priority = null, $status = null);
    public function getTaskById($id);
    public function createTask(array $data);
    public function updateTask($id, array $data);
    public function deleteTask($id);

    public function getTechnicianTasks(int $technicianId);
}
