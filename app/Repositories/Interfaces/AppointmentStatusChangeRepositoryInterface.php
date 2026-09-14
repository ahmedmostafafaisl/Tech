<?php

namespace App\Repositories\Interfaces;

interface AppointmentStatusChangeRepositoryInterface
{
    public function store(array $data);
    public function update($id, array $data);
    public function getAll();
    public function getById($id);
    public function delete($id);
}
