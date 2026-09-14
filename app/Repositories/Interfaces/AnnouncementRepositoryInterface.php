<?php

namespace App\Repositories\Interfaces;


use Illuminate\Database\Eloquent\Collection;
use App\Models\Announcement;

interface AnnouncementRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Announcement;
    public function create(array $data): Announcement;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}
