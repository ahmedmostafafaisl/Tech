<?php

namespace App\Http\Resources\Task;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TaskResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'technician_id' => $this->technician_id,
            'technician_name' => $this->technician ? $this->technician->username : null,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'note' => $this->note,
            'icon' => $this->icon
                ? Storage::disk('s3')->temporaryUrl($this->icon, now()->addMinutes(100))
                : null,
            'images' => $this->images->map(function ($image) {
                return Storage::disk('s3')->temporaryUrl($image->image, now()->addMinutes(100));
            }),

            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getImageUrl($path)
    {
        if ($path && Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(100));
        }
        return null;
    }
}
