<?php

namespace App\Http\Controllers\Api\ChangeRequestReason;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\UpsertChangeRequestRequest;
use App\Models\ChangeRequest;

class ChangeRequestController extends Controller
{
    /**
     * Create a new ChangeRequest, or update the existing one for this
     * appointment if it already exists — matched by (book_id, sales_order_id),
     * since that pair uniquely identifies which appointment the change
     * request belongs to.
     */
    public function upsert(UpsertChangeRequestRequest $request)
    {
        $validated = $request->validated();

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

        return response()->json([
            'status'  => true,
            'message' => $changeRequest->wasRecentlyCreated
                ? 'Change request created successfully.'
                : 'Change request updated successfully.',
            'data'    => $changeRequest,
        ], $changeRequest->wasRecentlyCreated ? 201 : 200);
    }
}
