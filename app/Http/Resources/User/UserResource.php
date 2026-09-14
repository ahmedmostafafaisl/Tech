<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->image,
            'status' => $this->status,
            'role' => $this->getRoleNames()->first(), // Assuming a single role per user
            'tech_id' => $this->tech_id,
            'warehouse_id' => $this->warehouse_id,
            'personnel_number' => $this->personnel_number,
            'fcm_token' => $this->fcm_token,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
