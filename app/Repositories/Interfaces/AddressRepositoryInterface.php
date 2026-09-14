<?php

namespace App\Repositories\Interfaces;


interface AddressRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function getAuthCustomerAddresses();
    public function getCustomerAddresses($id);
}
