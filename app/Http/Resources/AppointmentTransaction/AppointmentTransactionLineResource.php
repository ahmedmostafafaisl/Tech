<?php
// ===== AppointmentTransactionLineResource.php =====

namespace App\Http\Resources\AppointmentTransaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentTransactionLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'appointment_transaction_id' => $this->appointment_transaction_id,
            'sales_line_rec_id'          => $this->sales_line_rec_id,
            'item_number'                => $this->item_number,
            'quantity'                   => $this->quantity,
            'order_type_rec_id'          => $this->order_type_rec_id,
            'warranty_status'            => $this->warranty_status,
            'serials'                    => AppointmentTransactionSerialResource::collection($this->whenLoaded('serials')),
            'created_at'                 => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
