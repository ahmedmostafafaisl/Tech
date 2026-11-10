<?php

namespace App\Http\Controllers\Api\InvoiceWhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceWhatsApp\ListInvoiceWhatsAppMessagesRequest;
use App\Http\Requests\InvoiceWhatsApp\SendInvoiceWhatsAppRequest;
use App\Jobs\SendInvoiceWhatsAppMessageJob;
use App\Models\InvoiceWhatsAppMessage;
use App\Models\Setting;

/**
 * The single public entry point for invoice sending (POST /invoice/send-link) — InvoiceController::
 * sendInvoiceLink() and the old SendInvoiceLinkJob/short_links cycle are no longer reachable from any route.
 * Gated by new_invoice_whatsapp_active, now defaulting to ON since this is the only invoice-sending path,
 * not an opt-in experiment — still checked before anything is queued, so turning it off (e.g. during an
 * incident) says so immediately rather than silently queuing a job that will no-op.
 */
class InvoiceWhatsAppController extends Controller
{
    public function send(SendInvoiceWhatsAppRequest $request)
    {
        if (! Setting::isActive('new_invoice_whatsapp_active', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Sending invoices via WhatsApp (new cycle) is currently disabled.',
            ], 503);
        }

        SendInvoiceWhatsAppMessageJob::dispatch(
            $request->input('book_id'),
            $request->input('phone'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Processing — the invoice will be sent via WhatsApp once confirmed with Dynamics.',
        ], 202);
    }

    /**
     * Mirrors ShortLinkController::index() (the equivalent listing for the existing cycle) — same filter
     * shape, same pagination style — but reading from invoice_whatsapp_messages, the new cycle's own table.
     */
    public function index(ListInvoiceWhatsAppMessagesRequest $request)
    {
        $validated = $request->validated();

        $query = InvoiceWhatsAppMessage::query();

        if (!empty($validated['book_id'])) {
            $query->where('book_id', $validated['book_id']);
        }

        if (!empty($validated['phone'])) {
            $query->where('phone', $validated['phone']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['message_id'])) {
            $query->where('message_id', $validated['message_id']);
        }

        if (!empty($validated['delivery_status'])) {
            $query->where('delivery_status', $validated['delivery_status']);
        }

        if (!empty($validated['error'])) {
            $query->where('error', 'like', '%' . $validated['error'] . '%');
        }

        if (!empty($validated['delivery_error'])) {
            $query->where('delivery_error', 'like', '%' . $validated['delivery_error'] . '%');
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $sortBy        = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $perPage     = $validated['per_page'] ?? 10;
        $currentPage = $validated['page'] ?? 1;

        \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'total_pages'  => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }
}
