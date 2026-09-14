<?php

namespace App\Http\Controllers\Item;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Item\ItemResource;
use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Repositories\Interfaces\ItemRepositoryInterface;

class ItemController extends Controller
{
    private $repository;

    public function __construct(ItemRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return ItemResource::collection($this->repository->all());
    }

    public function store(StoreItemRequest $request)
    {
        return new ItemResource($this->repository->create($request->validated()));
    }

    public function show($id)
    {
        return new ItemResource($this->repository->find($id));
    }

    public function update(UpdateItemRequest $request, $id)
    {
        return new ItemResource($this->repository->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
