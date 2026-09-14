<?php

namespace App\Repositories\Item;

use App\Models\Item;
use App\Repositories\Interfaces\ItemRepositoryInterface;



class ItemRepository implements ItemRepositoryInterface
{
    public function all()
    {
        return Item::all();
    }

    public function find($id)
    {
        return Item::findOrFail($id);
    }

    public function create(array $data)
    {
        if (isset($data['warranty']) && $data['warranty'] == 0) {
            $data['warranty_period'] = null;
        }
        return Item::create($data);
    }

    public function update($id, array $data)
    {
        if (isset($data['warranty']) && $data['warranty'] == 0) {
            $data['warranty_period'] = null;
        }
        $item = Item::findOrFail($id);
        $item->update($data);
        return $item;
    }

    public function delete($id)
    {
        return Item::destroy($id);
    }
}
