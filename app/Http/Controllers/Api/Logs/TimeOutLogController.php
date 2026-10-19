<?php

namespace App\Http\Controllers\Api\Logs;

use App\Models\TimeOutLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class TimeOutLogController extends Controller
{
    /**
     * tech_id previously came straight from the request body with no check against the caller — any
     * authenticated user could create a timeout log entry attributed to a different technician. Now
     * derived from the authenticated user, the same way TechController resolves it elsewhere.
     */
    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
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

        $user = $request->user();
        $techId = $user->tech_id ?? $user->technician_rec_id;

        $log = TimeOutLog::create([
            'tech_id' => $techId,
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

    /**
     * Previously reachable for any log id by any authenticated user, and $request->all() let tech_id
     * itself be silently reassigned (the model's $fillable did limit it to the four real columns, but
     * tech_id — the thing this audit trail is supposed to attribute — was one of them). Now owner-or-admin,
     * and tech_id is never changeable through this endpoint at all, admin included: nothing legitimately
     * reassigns who a log entry belongs to after the fact.
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'id' => 'required|exists:time_out_logs,id',
            'route' => 'sometimes|string',
            'body' => 'sometimes|string',
            'time' => 'sometimes',
        ]);

        $log = TimeOutLog::find($request->id);

        $authUser = auth()->user();
        $callerTechId = $authUser ? ($authUser->tech_id ?? $authUser->technician_rec_id) : null;
        $isOwner = $callerTechId !== null && (string) $callerTechId === (string) $log->tech_id;
        $isAdmin = $authUser?->hasAnyRole(['super_admin', 'admin']) ?? false;

        if (!$isOwner && !$isAdmin) {
            abort(403, 'You are not authorized to update this log.');
        }

        $log->update($request->only(['route', 'body', 'time']));

        return response()->json([
            'status' => 200,
            'message' => 'Timeout log updated successfully.',
            'data' => $log,
        ]);
    }
}
