<?php
// ===== AppointmentTransactionResource.php =====

namespace App\Http\Resources\AppointmentTransaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'book_id'    => $this->book_id,
            'rec_id'     => $this->rec_id,
            'tech_id'    => $this->tech_id,
            'lines'      => AppointmentTransactionLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
