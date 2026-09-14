<?php

namespace App\Repositories\Interfaces;

interface AppointmentChangeStatusRequestInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);

    //    getAppointmentChangeStatusRequests
    public function getMyRequests();
    public function syncAllTechnicianChangeStatusRequests(array $requests);
}
