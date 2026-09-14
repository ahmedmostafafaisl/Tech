<?php

namespace App\Http\Controllers\Part;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Part\PartResource;
use App\Http\Requests\Part\StorePartRequest;
use App\Http\Requests\Part\UpdatePartRequest;
use App\Repositories\Interfaces\PartRepositoryInterface;

class PartController extends Controller
{
    protected $partRepository;

    public function __construct(PartRepositoryInterface $partRepository)
    {
        $this->partRepository = $partRepository;
    }

    public function index(): JsonResponse
    {
        return response()->json(PartResource::collection($this->partRepository->getAll()));
    }

    public function show($id): JsonResponse
    {
        return response()->json(new PartResource($this->partRepository->getById($id)));
    }

    public function store(StorePartRequest $request): JsonResponse
    {
        $part = $this->partRepository->store($request->validated());
        return response()->json(new PartResource($part), 201);
    }

    public function update(UpdatePartRequest $request, $id): JsonResponse
    {
        $part = $this->partRepository->update($id, $request->validated());
        return response()->json(new PartResource($part));
    }

    public function destroy($id): JsonResponse
    {
        $this->partRepository->delete($id);
        return response()->json(['message' => 'Part deleted successfully']);
    }
}
