<?php
// ===== AppointmentTransactionSerialResource.php =====

namespace App\Http\Resources\AppointmentTransaction;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentTransactionSerialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                              => $this->id,
            'appointment_transaction_line_id' => $this->appointment_transaction_line_id,
            'sales_line_rec_id'               => $this->sales_line_rec_id,
            'item_number'                     => $this->item_number,
            'serial'                          => $this->serial,
            'created_at'                      => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
