<?php

namespace App\Repositories\Announcement;


use App\Models\Announcement;
use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;

class AnnouncementRepository implements AnnouncementRepositoryInterface
{
    public function all(): Collection
    {
        return Announcement::all();
    }

    public function find(int $id): ?Announcement
    {
        return Announcement::find($id);
    }

    public function create(array $data): Announcement
    {
        return Announcement::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $announcement = $this->find($id);
        return $announcement ? $announcement->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $announcement = $this->find($id);
        return $announcement ? $announcement->delete() : false;
    }
}
