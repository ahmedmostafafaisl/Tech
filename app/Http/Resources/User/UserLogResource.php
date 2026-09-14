<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLogResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'action_date' => $this->action_date,
            'created_at' => $this->created_at,
        ];
    }
}
