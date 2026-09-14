<?php

namespace App\Repositories\Part;

use App\Models\Part;
use App\Repositories\Interfaces\PartRepositoryInterface;

class PartRepository implements PartRepositoryInterface
{
    public function getAll()
    {
        return Part::all();
    }

    public function getById($id)
    {
        return Part::findOrFail($id);
    }

    public function store(array $data)
    {
        return Part::create($data);
    }

    public function update($id, array $data)
    {
        $part = Part::findOrFail($id);
        $part->update($data);
        return $part;
    }

    public function delete($id)
    {
        return Part::destroy($id);
    }
}
