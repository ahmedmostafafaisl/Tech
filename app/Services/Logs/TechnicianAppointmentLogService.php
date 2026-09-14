<?php

namespace App\Services\Logs;

use App\Models\TechnicianAppointmentLog;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TechnicianAppointmentLogService
{
    public function log(
        ?int $techId,
        string $action,
        string $status,
        ?string $bookId = null,
        ?string $salesOrderId = null,
        ?string $message = null,
        mixed $requestPayload = null,
        mixed $responsePayload = null,
        ?string $error = null,
        ?int $userId = null,
        ?array $meta = null,
    ): TechnicianAppointmentLog {
        return TechnicianAppointmentLog::create([
            'user_id'          => $userId ?? Auth::id(),
            'tech_id'          => $techId,
            'book_id'          => $bookId,
            'sales_order_id'   => $salesOrderId,
            'action'           => $action,
            'status'           => $status,
            'message'          => $message,
            'error'            => $error,
            'request_payload'  => $this->normalize($requestPayload),
            'response_payload' => $this->normalize($responsePayload),
            'meta'             => $this->normalize($meta),
        ]);
    }

    public function success(
        ?int $techId,
        string $action,
        ?string $bookId = null,
        ?string $salesOrderId = null,
        ?string $message = null,
        mixed $requestPayload = null,
        mixed $responsePayload = null,
        ?int $userId = null,
        ?array $meta = null,
    ): TechnicianAppointmentLog {
        return $this->log(
            techId: $techId,
            action: $action,
            status: 'success',
            bookId: $bookId,
            salesOrderId: $salesOrderId,
            message: $message,
            requestPayload: $requestPayload,
            responsePayload: $responsePayload,
            userId: $userId,
            meta: $meta,
        );
    }

    public function failed(
        ?int $techId,
        string $action,
        ?string $bookId = null,
        ?string $salesOrderId = null,
        ?string $message = null,
        mixed $requestPayload = null,
        mixed $responsePayload = null,
        ?string $error = null,
        ?int $userId = null,
        ?array $meta = null,
    ): TechnicianAppointmentLog {
        return $this->log(
            techId: $techId,
            action: $action,
            status: 'failed',
            bookId: $bookId,
            salesOrderId: $salesOrderId,
            message: $message,
            requestPayload: $requestPayload,
            responsePayload: $responsePayload,
            error: $error,
            userId: $userId,
            meta: $meta,
        );
    }

    public function validationFailed(
        ?int $techId,
        string $action,
        ?string $bookId = null,
        ?string $salesOrderId = null,
        ?string $message = 'Validation failed',
        mixed $requestPayload = null,
        mixed $responsePayload = null,
        ?int $userId = null,
        ?array $meta = null,
    ): TechnicianAppointmentLog {
        return $this->log(
            techId: $techId,
            action: $action,
            status: 'validation_failed',
            bookId: $bookId,
            salesOrderId: $salesOrderId,
            message: $message,
            requestPayload: $requestPayload,
            responsePayload: $responsePayload,
            userId: $userId,
            meta: $meta,
        );
    }

    public function unauthorized(
        ?int $techId,
        string $action,
        ?string $bookId = null,
        ?string $salesOrderId = null,
        ?string $message = 'Unauthorized technician action',
        mixed $requestPayload = null,
        mixed $responsePayload = null,
        ?string $error = null,
        ?int $userId = null,
        ?array $meta = null,
    ): TechnicianAppointmentLog {
        return $this->log(
            techId: $techId,
            action: $action,
            status: 'unauthorized',
            bookId: $bookId,
            salesOrderId: $salesOrderId,
            message: $message,
            requestPayload: $requestPayload,
            responsePayload: $responsePayload,
            error: $error,
            userId: $userId,
            meta: $meta,
        );
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'message' => $value->getMessage(),
                'file'    => $value->getFile(),
                'line'    => $value->getLine(),
            ];
        }

        return $value;
    }
}
