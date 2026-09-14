<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Http\Controllers\Controller;
use App\Models\ShortLink;
use Illuminate\Http\Request;

class ShortLinkController extends Controller
{
    public function show(string $code)
    {
        $link = ShortLink::where('code', $code)->firstOrFail();

        $link->increment('hits');

        return redirect()->away($link->url);
    }

    public function index(Request $request)
    {
        $bookId = $request->query('book_id');
        $perPage = (int) $request->query('per_page', 10);
        $currentPage = (int) $request->query('page', 1);

        $query = ShortLink::query()->latest();

        if ($bookId) {
            $query->where('book_id', $bookId);
        }

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
