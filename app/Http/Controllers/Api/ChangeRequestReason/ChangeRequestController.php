<?php

namespace App\Http\Controllers\Api\ChangeRequestReason;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\UpsertChangeRequestRequest;
use App\Models\ChangeRequest;
use App\Services\Logs\TechnicianAppointmentLogService;

class ChangeRequestController extends Controller
{
    public function __construct(
        private readonly TechnicianAppointmentLogService $logService,
    ) {}

    /**
     * Create a new ChangeRequest, or update the existing one for this
     * appointment if it already exists — matched by (book_id, sales_order_id),
     * since that pair uniquely identifies which appointment the change
     * request belongs to.
     *
     * Every call is logged (technician_appointment_logs, via the same service used elsewhere in this
     * codebase for request auditing) — this endpoint sits behind dy.auth, not a logged-in technician
     * session, so it's DY365 itself calling in, and having a record of exactly what it sent and when is
     * valuable on its own, not just for diagnosing failures.
     */
    public function upsert(UpsertChangeRequestRequest $request)
    {
        $validated = $request->validated();

        try {
            $changeRequest = ChangeRequest::updateOrCreate(
                [
                    'book_id'        => $validated['book_id'],
                    'sales_order_id' => $validated['sales_order_id'],
                ],
                [
                    'tech_id'       => $validated['tech_id'],
                    'request_type'  => $validated['request_type'],
                    'reason_rec_id' => $validated['reason_rec_id'] ?? null,
                    'notes'         => $validated['notes'] ?? null,
                ]
            );

            $message = $changeRequest->wasRecentlyCreated
                ? 'Change request created successfully.'
                : 'Change request updated successfully.';

            $this->logService->success(
                techId: (int) $validated['tech_id'],
                action: 'change_request_upsert',
                bookId: $validated['book_id'],
                salesOrderId: $validated['sales_order_id'],
                message: $message,
                requestPayload: $validated,
                responsePayload: $changeRequest->toArray(),
                meta: ['created' => $changeRequest->wasRecentlyCreated],
            );

            return response()->json([
                'status'  => true,
                'message' => $message,
                'data'    => $changeRequest,
            ], $changeRequest->wasRecentlyCreated ? 201 : 200);
        } catch (\Throwable $e) {
            $this->logService->failed(
                techId: isset($validated['tech_id']) ? (int) $validated['tech_id'] : null,
                action: 'change_request_upsert',
                bookId: $validated['book_id'] ?? null,
                salesOrderId: $validated['sales_order_id'] ?? null,
                message: 'Change request upsert failed.',
                requestPayload: $validated,
                error: $e,
            );

            throw $e;
        }
    }
}
