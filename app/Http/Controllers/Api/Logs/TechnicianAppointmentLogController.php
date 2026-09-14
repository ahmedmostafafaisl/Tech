<?php

namespace App\Http\Controllers\Api\Logs;

use App\Http\Controllers\Controller;
use App\Http\Resources\Logs\TechnicianAppointmentLogResource;
use App\Models\TechnicianAppointmentLog;
use Illuminate\Http\Request;

class TechnicianAppointmentLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'book_id' => 'nullable|string|max:255',
            'sales_order_id' => 'nullable|string|max:255',
            'tech_id' => 'nullable|integer',
            'action' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = TechnicianAppointmentLog::query();

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id);
        }

        if ($request->filled('sales_order_id')) {
            $query->where('sales_order_id', $request->sales_order_id);
        }

        if ($request->filled('tech_id')) {
            $query->where('tech_id', $request->tech_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('book_id', 'like', "%{$search}%")
                    ->orWhere('sales_order_id', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('error', 'like', "%{$search}%");
            });
        }

        $logs = $query
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => TechnicianAppointmentLogResource::collection($logs->items()),
            'filters' => [
                'book_id' => $request->input('book_id'),
                'sales_order_id' => $request->input('sales_order_id'),
                'tech_id' => $request->input('tech_id'),
                'action' => $request->input('action'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'total_pages' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total_items' => $logs->total(),
            ],
        ]);
    }

    public function byBookId(string $book_id, Request $request)
    {
        $request->validate([
            'sales_order_id' => 'nullable|string|max:255',
            'tech_id' => 'nullable|integer',
            'action' => 'nullable|string|max:100',
            'status' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = TechnicianAppointmentLog::query()
            ->where('book_id', $book_id);

        if ($request->filled('sales_order_id')) {
            $query->where('sales_order_id', $request->sales_order_id);
        }

        if ($request->filled('tech_id')) {
            $query->where('tech_id', $request->tech_id);
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('book_id', 'like', "%{$search}%")
                    ->orWhere('sales_order_id', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('error', 'like', "%{$search}%");
            });
        }

        $logs = $query
            ->latest('id')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => TechnicianAppointmentLogResource::collection($logs->items()),
            'filters' => [
                'book_id' => $book_id,
                'sales_order_id' => $request->input('sales_order_id'),
                'tech_id' => $request->input('tech_id'),
                'action' => $request->input('action'),
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'total_pages' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total_items' => $logs->total(),
            ],
        ]);
    }
}
