<?php

namespace App\Repositories\Interfaces;



interface WarehouseInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);

    // create or update warehouse data from the dy365
    public function syncWarehouses(array $warehouses): void;

    // get all Inventory

    public function getInventory($perPage, $page, $type = null);

    public function syncWarehouseStock(array $products): void;
}
