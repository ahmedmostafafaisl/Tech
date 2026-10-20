<?php

namespace App\Http\Controllers\Api\TransmittedSerial;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransmittedSerial\ListTransmittedSerialsRequest;
use App\Models\TransmittedSerial;

/**
 * Admin-only browsing/maintenance of the transmitted_serials index (see TransmittedSerialIndexer /
 * PermanentlyTransmittedSerials) — not something a technician or customer has any reason to see or touch
 * directly; this is purely an operational tool for investigating or correcting the permanent-exclusion
 * index itself.
 */
class TransmittedSerialController extends Controller
{
    public function index(ListTransmittedSerialsRequest $request)
    {
        $validated = $request->validated();

        $query = TransmittedSerial::query();

        if (!empty($validated['book_id'])) {
            $query->where('book_id', $validated['book_id']);
        }

        if (!empty($validated['tech_id'])) {
            $query->where('tech_id', $validated['tech_id']);
        }

        if (!empty($validated['item_number'])) {
            $query->where('item_number', $validated['item_number']);
        }

        if (!empty($validated['serial'])) {
            $query->where('serial', $validated['serial']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        if (!empty($validated['search'])) {
            $term = $validated['search'];
            $query->where(function ($q) use ($term) {
                $q->where('book_id', 'like', "%{$term}%")
                    ->orWhere('item_number', 'like', "%{$term}%")
                    ->orWhere('serial', 'like', "%{$term}%")
                    ->orWhere('tech_id', 'like', "%{$term}%");
            });
        }

        $sortBy        = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $validated['per_page'] ?? 20;

        return response()->json($query->paginate($perPage));
    }

    /**
     * Deletes one row from the permanent-exclusion index directly — the serial it covered is no longer
     * treated as transmitted anywhere, immediately, for every endpoint that shares this index (
     * singleAppointmentByBookId, mainWarehouse, SendPaymentLinksRequest, NewCompleteAppointmentRequest).
     * Intended as a manual correction for the index itself (e.g. a row that was indexed in error), not a
     * way to resolve a real business dispute about an appointment or a change request.
     */
    public function destroy(TransmittedSerial $transmittedSerial)
    {
        $transmittedSerial->delete();

        return response()->json(['status' => true, 'message' => 'Transmitted serial deleted successfully.']);
    }
}
