<?php

namespace App\Http\Controllers\Api\Logs;

use App\Models\TimeOutLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TimeOutLogController extends Controller
{
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'tech_id' => 'required|string',
            'route' => 'required|string',
            'body' => 'nullable|string',
            'time' => 'nullable',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validated->errors(),
            ], 400);
        }

        $log = TimeOutLog::create([
            'tech_id' => $request->tech_id,
            'route' => $request->route,
            'body' => $request->body,
            'time' => $request->time ?? now(),
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Timeout log created successfully.',
            'data' => $log,
        ]);
    }

    public function index(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'tech_id' => 'nullable|string',
            'route' => 'nullable|string',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d|after_or_equal:from_date',
            'currentPage' => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validated->errors(),
            ], 400);
        }

        $query = TimeOutLog::query()->orderBy('created_at', 'desc');

        // 🔍 Apply filters
        if ($request->filled('tech_id')) {
            $query->where('tech_id', $request->tech_id);
        }

        if ($request->filled('route')) {
            $query->where('route', 'LIKE', '%' . $request->route . '%');
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [$request->from_date, $request->to_date]);
        }

        // 📄 Pagination setup
        $currentPage = $request->input('currentPage', 1);
        $pageSize = $request->input('pageSize', 10);
        $total = $query->count();

        $logs = $query->skip(($currentPage - 1) * $pageSize)
            ->take($pageSize)
            ->get();

        $totalPages = ceil($total / $pageSize);

        return response()->json([
            'status' => 200,
            'data' => [
                'timeout_logs' => $logs,
                'pagination' => [
                    'current_page' => (int) $currentPage,
                    'total_pages' => $totalPages,
                    'per_page' => (int) $pageSize,
                    'total_items' => $total,
                ],
            ],
            'message' => 'success',
        ]);
    }

    // update method to be implemented
    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:time_out_logs,id',
            'tech_id' => 'sometimes|string',
            'route' => 'sometimes|string',
            'body' => 'sometimes|string',
            'time' => 'sometimes',
        ]);
        $log = TimeOutLog::find($request->id);
        $log->update($request->all());

        return response()->json([
            'status' => 200,
            'message' => 'Timeout log updated successfully.',
            'data' => $log,
        ]);
    }
}
