<?php

namespace App\Repositories\Dashboard;

use App\Models\AppointmentTransaction;
use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use App\Models\PreAppointmentMessage;
use App\Models\TechnicianLog;
use App\Models\User;
use App\Repositories\Interfaces\DashboardRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getAllTechnicians(Request $request): array
    {
        $query = User::where('type', 'tech');

        // ✅ filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ search by username OR phone OR tech_id
        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('username', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('tech_id', 'LIKE', "%{$search}%");
            });
        }

        return $this->paginate($query, $request);
    }

    public function getSingleTechnician(int $id)
    {
        return User::where('type', 'tech')->find($id);
    }

    public function getDirectAppointments(Request $request): array
    {
        $query = DirectAppointment::with('technician');

        // Filter by sales_order_id
        if ($request->filled('sales_order_id')) {
            $query->where('sales_order_id', $request->sales_order_id)
                ->latest('created_at'); // latest record first
        }

        // Filter by book_id
        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id)
                ->latest('created_at'); // latest record first
        }

        // Filter by tech_id
        if ($request->filled('tech_id')) {
            $query->where('tech_id', $request->tech_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 🧮 Compute counts (always)
        $total_processing_complete = DirectAppointment::where('status', 'paid')
            ->where('complete_flag', 0)
            ->count();

        $total_completed_dy365 = DirectAppointment::where('status', 'paid')
            ->where('complete_flag', 1)
            ->count();

        // 📅 Filter by date range
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = Carbon::parse($request->from_date)->startOfDay();
            $to   = Carbon::parse($request->to_date)->endOfDay();

            $query->whereBetween('created_at', [$from, $to]);
        }

        // If sales_order_id or book_id is provided, return only 1 latest record
        if ($request->filled('sales_order_id') || $request->filled('book_id')) {
            $latestRecord = $query->first(); // get latest record
            $items = $latestRecord ? [$latestRecord] : [];

            return [
                'items' => $items,
                'pagination' => [
                    'current_page' => 1,
                    'total_pages' => 1,
                    'per_page' => 1,
                    'total_items' => count($items),
                ],
                'counts' => [
                    'total_processing_complete' => $total_processing_complete,
                    'total_completed_dy365' => $total_completed_dy365,
                ],
            ];
        }

        // Otherwise, paginate normally
        $result = $this->paginate($query, $request);

        // Add counts to the paginated result
        $result['counts'] = [
            'total_processing_complete' => $total_processing_complete,
            'total_completed_dy365' => $total_completed_dy365,
        ];

        return $result;
    }

    public function getSingleDirectAppointment(int $id)
    {
        $appointment = DirectAppointment::with(['technician', 'payments', 'attachments'])->find($id);

        if (!$appointment) {
            return null;
        }

        $transaction = AppointmentTransaction::with('lines.serials')
            ->where('book_id', $appointment->book_id)
            ->latest()
            ->first();


        $appointment->setRelation('lines', $transaction?->lines ?? collect());

        return $appointment;
    }

    public function getTechnicianLogs(Request $request, int $techId): array
    {
        return $this->paginate(
            TechnicianLog::where('action_by', $techId)->latest(),
            $request
        );
    }

    public function getTechnicianDirectAppointments(Request $request, int $techId): array
    {
        $query = DirectAppointment::with('technician')
            ->where('tech_id', $techId)
            ->when($request->book_id, fn($q) => $q->where('book_id', $request->book_id))
            ->when($request->sales_order_id, fn($q) => $q->where('sales_order_id', $request->sales_order_id));

        return $this->paginate($query, $request);
    }

    // send appointment reminder
    public function getLatestBySalesOrderId(string $salesOrderId): ?DirectAppointment
    {
        return DirectAppointment::where('sales_order_id', $salesOrderId)
            ->orderByDesc('id')
            ->first();
    }

    public function getPaidPayments(string $salesOrderId)
    {
        return DirectAppointmentPayment::where('sales_order_id', $salesOrderId)
            ->where('status', 'paid')
            ->get();
    }

    // public function getAllPreMessages(Request $request): array
    // {
    //     $query = PreAppointmentMessage::query();

    //     // 🔍 Filter by phone
    //     if ($request->filled('phone')) {
    //         $query->where('phone', 'like', "%{$request->phone}%")
    //             ->latest('created_at');
    //     }

    //     // 🔍 Filter by sales_order
    //     if ($request->filled('sales_order')) {
    //         $query->where('sales_order', $request->sales_order)
    //             ->latest('created_at');
    //     }

    //     // 🔍 Filter by book_id
    //     if ($request->filled('book_id')) {
    //         $query->where('book_id', $request->book_id)
    //             ->latest('created_at');
    //     }

    //     // 📅 Filter by a single exact date
    //     if ($request->filled('date')) {
    //         $query->whereDate('date', Carbon::parse($request->date)->toDateString());
    //     }

    //     // 📅 Filter by date range — each bound now works independently
    //     if ($request->filled('from_date')) {
    //         $query->where('created_at', '>=', Carbon::parse($request->from_date)->startOfDay());
    //     }

    //     if ($request->filled('to_date')) {
    //         $query->where('created_at', '<=', Carbon::parse($request->to_date)->endOfDay());
    //     }

    //     // ✅ Filter by is_sent
    //     if ($request->filled('is_sent')) {
    //         $query->where('is_sent', $request->boolean('is_sent'));
    //     }

    //     // 👤 Filter by customer_response
    //     if ($request->filled('customer_response')) {
    //         $query->where('customer_response', $request->customer_response);
    //     }

    //     // 🧮 Counts (always calculated)
    //     $total_sent = PreAppointmentMessage::where('is_sent', true)
    //         ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
    //         ->count();
    //     $total_pending = PreAppointmentMessage::where('customer_response', 'pending')
    //         ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
    //         ->count();
    //     $total_confirmed = PreAppointmentMessage::where('customer_response', 'confirm')
    //         ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
    //         ->count();
    //     $total_rescheduled = PreAppointmentMessage::where('customer_response', 'reschedule')
    //         ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
    //         ->count();
    //     $total_cancelled = PreAppointmentMessage::where('customer_response', 'cancel')
    //         ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])
    //         ->count();


    //     // 🎯 If exact identifiers provided → return latest single record
    //     if (
    //         $request->filled('sales_order') ||
    //         $request->filled('book_id') ||
    //         $request->filled('phone')
    //     ) {
    //         $latestRecord = $query->first();
    //         $items = $latestRecord ? [$latestRecord] : [];

    //         return [
    //             'items' => $items,
    //             'pagination' => [
    //                 'current_page' => 1,
    //                 'total_pages'  => 1,
    //                 'per_page'     => 1,
    //                 'total_items'  => count($items),
    //             ],
    //             'counts' => [
    //                 'total_sent'        => $total_sent,
    //                 'total_pending'     => $total_pending,
    //                 'total_confirmed'   => $total_confirmed,
    //                 'total_rescheduled' => $total_rescheduled,
    //                 'total_cancelled'   => $total_cancelled,
    //             ],
    //         ];
    //     }

    //     // 📦 Normal pagination
    //     $result = $this->paginate($query, $request);

    //     // ➕ Append counts
    //     $result['counts'] = [
    //         'total_sent'        => $total_sent,
    //         'total_pending'     => $total_pending,
    //         'total_confirmed'   => $total_confirmed,
    //         'total_rescheduled' => $total_rescheduled,
    //         'total_cancelled'   => $total_cancelled,
    //     ];

    //     return $result;
    // }


    public function getPreMessageById(int $id): ?PreAppointmentMessage
    {
        return PreAppointmentMessage::find($id);
    }


    // send pre  appointment reminder
    public function getLatestBySalesOrder(string $salesOrder): ?PreAppointmentMessage
    {
        return PreAppointmentMessage::where('sales_order', $salesOrder)
            ->latest()
            ->first();
    }

    private function paginate($query, Request $request): array
    {
        $perPage = (int) $request->input('per_page', 10);
        $currentPage = (int) $request->input('currentPage', 1);

        $paginator = $query->paginate(
            $perPage,
            ['*'],
            'page',
            $currentPage
        );

        return [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'total_pages'  => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total_items'  => $paginator->total(),
            ],
        ];
    }


    /**
     * Builds the filtered query — shared by both getAllPreMessages() (paginated
     * list) and exportPreMessages() (full unpaginated export), so the two can
     * never drift out of sync on what "matching the filters" means.
     */
    public function buildFilteredPreMessagesQuery(Request $request)
    {
        $query = PreAppointmentMessage::query();

        // 🔍 Filter by phone
        if ($request->filled('phone')) {
            $query->where('phone', 'like', "%{$request->phone}%")
                ->latest('created_at');
        }

        // 🔍 Filter by sales_order
        if ($request->filled('sales_order')) {
            $query->where('sales_order', $request->sales_order)
                ->latest('created_at');
        }

        // 🔍 Filter by book_id
        if ($request->filled('book_id')) {
            $query->where('book_id', $request->book_id)
                ->latest('created_at');
        }

        // 🏷️ Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // 📅 Filter by a single exact date
        if ($request->filled('date')) {
            $query->whereDate('date', Carbon::parse($request->date)->toDateString());
        }

        // 📅 Filter by month — first day through last day of that month
        if ($request->filled('month')) {
            $monthStart = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();

            $query->whereBetween('created_at', [$monthStart, $monthEnd]);
        }

        // 📅 Filter by date range — each bound works independently
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->from_date)->startOfDay());
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->to_date)->endOfDay());
        }

        // ✅ Filter by is_sent
        if ($request->filled('is_sent')) {
            $query->where('is_sent', $request->boolean('is_sent'));
        }

        // 👤 Filter by customer_response
        if ($request->filled('customer_response')) {
            $query->where('customer_response', $request->customer_response);
        }

        return $query;
    }
    public function buildFilteredPreMessagesQueryMonth(\Illuminate\Http\Request $request)
    {
        // DB::table(), not Eloquent — this is a read-only export, no need for
        // model hydration overhead (casts/mutators/events) on every row.
        $query = DB::table('pre_appointment_messages')
            ->orderBy('id'); // required — Maatwebsite's FromQuery chunks
        // internally, and Laravel's chunk() refuses to
        // run without a deterministic order.

        if ($request->filled('month')) {
            $monthStart = Carbon::createFromFormat('Y-m', $request->month)->startOfMonth();
            $monthEnd   = $monthStart->copy()->endOfMonth();

            $query->whereBetween('created_at', [$monthStart, $monthEnd]);
        }

        return $query;
    }


    public function getAllPreMessages(Request $request): array
    {
        $query = $this->buildFilteredPreMessagesQuery($request);

        // 🧮 Counts — computed from the SAME filtered result set as the main
        // query (clone so these don't mutate $query itself), instead of being
        // hardcoded to "today". That was the bug: it ignored month/date/
        // phone/etc. filters entirely, so any request for non-today data
        // always came back all zeros regardless of how much actually matched.
        $total_sent        = (clone $query)->where('is_sent', true)->count();
        $total_pending     = (clone $query)->where('customer_response', 'pending')->count();
        $total_confirmed   = (clone $query)->where('customer_response', 'confirm')->count();
        $total_rescheduled = (clone $query)->where('customer_response', 'reschedule')->count();
        $total_cancelled   = (clone $query)->where('customer_response', 'cancel')->count();

        // 🎯 If exact identifiers provided → return latest single record
        if (
            $request->filled('sales_order') ||
            $request->filled('book_id') ||
            $request->filled('phone')
        ) {
            $latestRecord = $query->first();
            $items = $latestRecord ? [$latestRecord] : [];

            return [
                'items' => $items,
                'pagination' => [
                    'current_page' => 1,
                    'total_pages'  => 1,
                    'per_page'     => 1,
                    'total_items'  => count($items),
                ],
                'counts' => [
                    'total_sent'        => $total_sent,
                    'total_pending'     => $total_pending,
                    'total_confirmed'   => $total_confirmed,
                    'total_rescheduled' => $total_rescheduled,
                    'total_cancelled'   => $total_cancelled,
                ],
            ];
        }

        // 📦 Normal pagination
        $result = $this->paginate($query, $request);

        // ➕ Append counts
        $result['counts'] = [
            'total_sent'        => $total_sent,
            'total_pending'     => $total_pending,
            'total_confirmed'   => $total_confirmed,
            'total_rescheduled' => $total_rescheduled,
            'total_cancelled'   => $total_cancelled,
        ];

        return $result;
    }

    /**
     * Same filters as getAllPreMessages(), but returns every matching row
     * (no pagination) as an .xlsx download — see App\Exports\PreAppointmentMessagesExport,
     * which re-applies these same filters via FromQuery so large result sets
     * are streamed/chunked rather than loaded into memory all at once.
     */
    public function exportPreMessages(Request $request)
    {
        $path = 'exports/pre-appointment-messages/pre-appointment-messages-' . now()->format('Y-m-d_His') . '.xlsx';

        \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\PreAppointmentMessagesPowerBiExport($request),
            $path,
            's3'
        );

        $downloadUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url($path);

        return [
            'status'       => true,
            'message'      => 'Export ready.',
            'download_url' => $downloadUrl,
        ];
    }


    public function getYesterdayPreMessages(): \Illuminate\Support\Collection
    {
        $yesterday = now('Asia/Riyadh')->subDay()->format('Y-m-d');
        $cacheKey  = "pre_messages_yesterday:{$yesterday}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addHours(22), function () use ($yesterday) {
            $start = \Carbon\Carbon::parse($yesterday, 'Asia/Riyadh')->startOfDay();
            $end   = \Carbon\Carbon::parse($yesterday, 'Asia/Riyadh')->endOfDay();

            return PreAppointmentMessage::whereBetween('created_at', [$start, $end])
                ->orderBy('id')
                ->get();
        });
    }
}
