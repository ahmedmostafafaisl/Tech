<?php

namespace App\Http\Controllers\Api\Announcement;


use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\AnnouncementRequest;
use App\Http\Resources\Announcement\AnnouncementResource;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementRepositoryInterface $repository) {}

    public function index()
    {
        return AnnouncementResource::collection($this->repository->all());
    }

    public function store(AnnouncementRequest $request)
    {
        $announcement = $this->repository->create($request->validated());
        return new AnnouncementResource($announcement);
    }

    public function show(int $id)
    {
        $announcement = $this->repository->find($id);
        return $announcement ? new AnnouncementResource($announcement) : response()->json(['message' => 'Not Found'], 404);
    }

    public function update(AnnouncementRequest $request, int $id)
    {
        $updated = $this->repository->update($id, $request->validated());
        return $updated ? response()->json(['message' => 'Updated']) : response()->json(['message' => 'Not Found'], 404);
    }

    public function destroy(int $id)
    {
        $deleted = $this->repository->delete($id);
        return $deleted ? response()->json(['message' => 'Deleted']) : response()->json(['message' => 'Not Found'], 404);
    }
}
