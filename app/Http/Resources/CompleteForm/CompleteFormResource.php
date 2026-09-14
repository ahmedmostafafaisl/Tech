<?php

namespace App\Http\Resources\CompleteForm;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompleteFormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'sales_order_id' => $this->sales_order_id,
            'book_id' => $this->book_id,
            'service_name' => $this->service_name,
            'notes' => $this->notes,

            'home_salt' => $this->home_salt,
            'home_salt_image' => $this->s3Url($this->home_salt_image),

            'device_salt' => $this->device_salt,
            'device_salt_image' => $this->s3Url($this->device_salt_image),

            'carbon_depletion' => (bool) $this->carbon_depletion,
            'sink_cleaning' => (bool) $this->sink_cleaning,
            'drain_connection' => (bool) $this->drain_connection,

            'carbon_depletion_image' => $this->s3Url($this->carbon_depletion_image),
            'sink_cleaning_image' => $this->s3Url($this->sink_cleaning_image),
            'drain_connection_image' => $this->s3Url($this->drain_connection_image),
            'additional_image' => $this->s3UrlArray($this->additional_image),

            'problem' => $this->problem,
            'solution' => $this->solution,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Generate public S3 URL from path or return null
     */
    private function s3Url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return Storage::disk('s3')->url($path);
    }

    private function s3UrlArray(array|string|null $value): array
    {
        if (empty($value)) {
            return [];
        }

        // already decoded by model cast
        if (is_array($value)) {
            $paths = $value;
        } else {
            $paths = json_decode($value, true);
        }

        if (!is_array($paths)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn($path) => $this->s3Url($path), $paths)
        ));
    }
}
