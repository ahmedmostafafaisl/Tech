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
            // null when this tech has no terminal assigned yet. Accessing the relationship directly lazy
            // loads it if it wasn't eager loaded — fine here since this resource always serialises one user
            // at a time (verify-pin, etc.), never a list, so there's no N+1 risk to guard against.
            'nearpay_terminal' => $this->nearPayTerminal ? [
                'id' => $this->nearPayTerminal->id,
                'terminal_id' => $this->nearPayTerminal->terminal_id,
                'name' => $this->nearPayTerminal->name,
                'assigned_at' => optional($this->nearPayTerminal->assigned_at)->format('Y-m-d H:i:s'),
            ] : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
