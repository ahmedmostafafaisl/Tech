<?php

namespace App\Http\Controllers\Api\Lead;

use App\Http\Controllers\Controller;
use App\Models\OrderLead;
use App\Services\Lead\LeadService;
use App\Services\Lead\ResponseService;
use App\Services\Lead\ScoringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService     $leadService,
        private readonly ResponseService $responseService,
        private readonly ScoringService  $scoringService,
    ) {}

    // ──────────────────────────────────────────────────────────────────
    // GET /api/leads
    // ──────────────────────────────────────────────────────────────────

    /**
     * List all order leads with responses.
     *
     * Filters (all optional):
     *   ?mobile_number=201116405941       partial match
     *   ?order_number=ORD-20260624-00002  exact match
     *   ?status=q1_sent|q2_sent|scored…
     *   ?priority=hot|warm|cold
     *   ?from_date=2026-06-01
     *   ?to_date=2026-06-30
     *   ?per_page=20  (default 20)
     *
     * If mobile_number or order_number is provided → returns the single
     * latest matching record (same pattern as your PreAppointmentMessage API).
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderLead::with('responses')->latest();

        // 🔍 Filter by mobile number (partial)
        if ($request->filled('mobile_number')) {
            $query->where('mobile_number', 'like', "%{$request->mobile_number}%");
        }

        // 🔍 Filter by order number (exact)
        if ($request->filled('order_number')) {
            $query->where('order_number', $request->order_number);
        }

        // 🔍 Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🔍 Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // 📅 Filter by date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // 🧮 Today's counts
        $today = [
            now()->startOfDay(),
            now()->endOfDay(),
        ];

        $todayQuery = fn() => OrderLead::whereBetween('created_at', $today);

        $counts = [
            'total_today'   => $todayQuery()->count(),
            'total_scored'  => $todayQuery()->where('status', 'scored')->count(),
            'total_pending' => $todayQuery()->whereIn('status', [
                'pending',
                'q1_sent',
                'q1_answered',
                'q2_sent',
                'q2_answered',
            ])->count(),
            'total_hot'     => $todayQuery()->where('priority', 'hot')->count(),
            'total_warm'    => $todayQuery()->where('priority', 'warm')->count(),
            'total_cold'    => $todayQuery()->where('priority', 'cold')->count(),
        ];

        // 🎯 Search by identifier (return latest matching record only)
        if ($request->filled('mobile_number') || $request->filled('order_number')) {
            $record = $query->first();

            return response()->json([
                'items' => $record ? [$record] : [],
                'pagination' => [
                    'current_page' => 1,
                    'total_pages'  => 1,
                    'per_page'     => 1,
                    'total_items'  => $record ? 1 : 0,
                ],
                'counts' => $counts,
            ]);
        }

        // 📦 Pagination
        $perPage = max(1, (int) $request->input('per_page', 20));

        // Support both "current_page" and "page"
        $currentPage = (int) $request->input(
            'current_page',
            $request->input('page', 1)
        );

        $currentPage = max(1, $currentPage);

        $total = (clone $query)->count();

        $totalPages = max(1, (int) ceil($total / $perPage));

        // Prevent requesting pages beyond the last page
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }

        $items = $query
            ->skip(($currentPage - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'items' => $items,
            'pagination' => [
                'current_page' => $currentPage,
                'total_pages'  => $totalPages,
                'per_page'     => $perPage,
                'total_items'  => $total,
            ],
            'counts' => $counts,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // GET /api/leads/{id}
    // ──────────────────────────────────────────────────────────────────

    /**
     * Single lead with full response breakdown.
     */
    public function show(string $id): JsonResponse
    {
        $lead = $this->leadService->getById($id, withResponses: true);

        if (! $lead) {
            return response()->json(['error' => 'Lead not found'], 404);
        }

        return response()->json($lead);
    }

    // ──────────────────────────────────────────────────────────────────
    // GET /api/leads/{id}/responses
    // ──────────────────────────────────────────────────────────────────

    /**
     * Both responses for a lead with scores and ref numbers.
     */
    public function responses(string $id): JsonResponse
    {
        $responses = $this->responseService->getResponsesForLead($id);

        return response()->json($responses);
    }

    // ──────────────────────────────────────────────────────────────────
    // POST /api/leads/{id}/score
    // ──────────────────────────────────────────────────────────────────

    /**
     * Manually trigger (or re-trigger) score calculation.
     */
    public function score(string $id): JsonResponse
    {
        try {
            $result = $this->scoringService->calculateAndSave($id);

            return response()->json([
                'lead_id'     => $result['lead']->id,
                'total_score' => $result['total_score'],
                'priority'    => $result['priority'],
                'q1_score'    => $result['q1_score'],
                'q2_score'    => $result['q2_score'],
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
