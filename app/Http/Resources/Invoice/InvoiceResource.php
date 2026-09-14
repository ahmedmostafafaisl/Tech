<?php

namespace App\Http\Resources\Invoice;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'invoice_status' => $this->invoice_status,
            'sub_total' => $this->sub_total,
            'discount' => $this->discount,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'vat' => $this->vat,
            'total' => $this->total,
            'invoice_url_pdf' => $this->invoice_url_pdf,
            'invoice_items' => $this->items,
            'created_at' => $this->created_at,
        ];
    }
}
