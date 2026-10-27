<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\ListShortLinksRequest;
use App\Models\ShortLink;

class ShortLinkController extends Controller
{
    public function show(string $code)
    {
        $link = ShortLink::where('code', $code)->firstOrFail();

        $link->increment('hits');

        return redirect()->away($link->url);
    }

    public function index(ListShortLinksRequest $request)
    {
        $validated = $request->validated();

        $query = ShortLink::query();

        if (!empty($validated['book_id'])) {
            $query->where('book_id', $validated['book_id']);
        }

        if (!empty($validated['phone'])) {
            $query->where('phone', $validated['phone']);
        }

        if (array_key_exists('sent', $validated) && $validated['sent'] !== null) {
            $query->where('sent', $request->boolean('sent'));
        }

        if (array_key_exists('invoice_generated', $validated) && $validated['invoice_generated'] !== null) {
            $query->where('invoice_generated', $request->boolean('invoice_generated'));
        }

        if (!empty($validated['last_error'])) {
            // free-text error message (e.g. "DY365 call failed: {exception message}" varies by failure) —
            // a partial match is far more useful here than requiring the exact string
            $query->where('last_error', 'like', '%' . $validated['last_error'] . '%');
        }

        if (!empty($validated['code'])) {
            $query->where('code', $validated['code']);
        }

        if (array_key_exists('hits', $validated) && $validated['hits'] !== null) {
            $query->where('hits', $validated['hits']);
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

        $perPage = $validated['per_page'] ?? 10;
        $currentPage = $validated['page'] ?? 1;

        \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return $currentPage;
        });

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]
        ]);
    }
}
