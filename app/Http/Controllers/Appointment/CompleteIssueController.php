<?php

namespace App\Http\Controllers\Appointment;

use Illuminate\Http\Request;
use App\Models\CompleteIssue;
use App\Http\Controllers\Controller;

class CompleteIssueController extends Controller
{
    public function index(Request $request)
    {
        $pageSize = $request->get('page_size', 10); // default 10 per page

        $issues = CompleteIssue::orderBy('id', 'desc')
            ->paginate($pageSize);

        return response()->json([
            'status' => true,
            'current_page' => $issues->currentPage(),
            'per_page' => $issues->perPage(),
            'total' => $issues->total(),
            'data' => $issues->items(),
        ]);
    }

    public function showBySalesOrder($sales_order_id)
    {
        $issues = CompleteIssue::where('sales_order_id', $sales_order_id)
            ->orderBy('id', 'desc')
            ->get();

        if ($issues->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => "No issues found for Sales Order {$sales_order_id}.",
            ], 404);
        }

        return response()->json([
            'status' => true,
            'sales_order_id' => $sales_order_id,
            'count' => $issues->count(),
            'data' => $issues,
        ]);
    }
}
