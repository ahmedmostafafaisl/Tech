<?php

namespace App\Http\Resources\Appointment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentStatusChangeResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'status' => $this->status,
            'reason' => $this->reason,
            'images' => $this->images->map(fn($image) => asset('storage/' . $image->image_path)),
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
