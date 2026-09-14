<?php

namespace App\Http\Resources\ChangeRequestReason;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChangeRequestReasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'ReasonRecId'  => $this->reason_rec_id,
            'ReasonType'    => $this->reason_type,
            'Reason'         => $this->reason,
            'title_ar'       => $this->title_ar,
            'title_en'       => $this->title_en,
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'     => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
