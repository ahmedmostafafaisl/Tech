<?php

namespace App\Repositories\Interfaces;

use Illuminate\Http\Request;

interface WarehouseTransferRepositoryInterface
{
    public function index();
    public function store(Request $request);
    public function update(Request $request, int $id);

    public function find($id);
    public function delete($id);

    public function getRequestsForTech(int $techId, ?string $status = null);
}
