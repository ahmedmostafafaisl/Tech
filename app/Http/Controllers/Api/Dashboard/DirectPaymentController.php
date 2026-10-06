<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\DirectPaymentIndexRequest;
use App\Http\Resources\Dashboard\DirectPaymentResource;
use App\Services\Payment\DirectPaymentListing;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/dashboard/direct-payments
 *
 * Direct payments made through Tabby, Tamara or ClickPay, paginated, filterable by type, date, reference id and
 * payment id. Behind auth:sanctum and role_or_permission:super_admin|admin|view payments (routes/api.php).
 */
class DirectPaymentController extends Controller
{
    public function index(DirectPaymentIndexRequest $request, DirectPaymentListing $listing): JsonResponse
    {
        $paginator = $listing->paginate($request->validated());

        return response()->json([
            'status'  => 200,
            'message' => 'Direct payments retrieved successfully.',
            'data'    => [
                'items'      => $paginator->getCollection()->map(fn ($payment) => (new DirectPaymentResource($payment))->resolve($request))->values(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'total_pages'  => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total_items'  => $paginator->total(),
                ],
            ],
        ]);
    }
}
