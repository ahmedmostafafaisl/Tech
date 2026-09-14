<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\GetAllTechniciansRequest;
use App\Http\Requests\Dashboard\GetDirectAppointmentsRequest;
use App\Http\Requests\Dashboard\PreAppointmentMessageIndexRequest;
use App\Http\Requests\Dashboard\PreAppointmentMessagePowerBiRequest;
use App\Http\Resources\Dashboard\PreAppointmentMessageResource;
use App\Repositories\Interfaces\DashboardRepositoryInterface;
use App\Services\Direct_appointment\DirectAppointmentService;
use App\Services\Logs\UserLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use ApiResponseHelper;
    protected DashboardRepositoryInterface $dashboardRepo;
    protected $service;
    protected $userLogService;

    public function __construct(DashboardRepositoryInterface $dashboardRepo, DirectAppointmentService $service, UserLogService $userLogService)
    {
        $this->dashboardRepo = $dashboardRepo;
        $this->service = $service;
        $this->userLogService = $userLogService;
    }

    // =============================
    // Technicians
    // =============================

    public function getAllTechnicians(GetAllTechniciansRequest $request)
    {
        return response()->json([
            'status'  => 200,
            'data'    => $this->dashboardRepo->getAllTechnicians($request),
            'message' => 'success',
        ]);
    }

    public function getSingleTechnician($id)
    {
        $technician = $this->dashboardRepo->getSingleTechnician($id);

        if (!$technician) {
            return response()->json([
                'status'  => false,
                'message' => 'Technician not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Technician details retrieved successfully.',
            'data'    => $technician,
        ], 200);
    }

    // =============================
    // Direct Appointments
    // =============================

    public function getDirectAppointments(GetDirectAppointmentsRequest $request)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view appointments')) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this appointment.')
                ->send();
        }

        return response()->json([
            'status'  => 200,
            'message' => 'Direct appointments retrieved successfully.',
            'data'    => $this->dashboardRepo->getDirectAppointments($request),
        ], 200);
    }

    public function getSingleDirectAppointment($id)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view appointments')) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this appointment.')
                ->send();
        }

        $appointment = $this->dashboardRepo->getSingleDirectAppointment($id);

        if (!$appointment) {
            return response()->json([
                'status'  => false,
                'message' => 'Direct appointment not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Direct appointment details retrieved successfully.',
            'data'    => $appointment,
        ], 200);
    }

    // =============================
    // Technician Logs
    // =============================

    public function getTechnicianLogs(Request $request, $techId)
    {
        return response()->json([
            'status'  => 200,
            'message' => 'success',
            'data'    => $this->dashboardRepo->getTechnicianLogs($request, $techId),
        ]);
    }

    // =============================
    // Technician Direct Appointments
    // =============================

    public function getTechnicianDirectAppointments(Request $request, $techId)
    {
        return response()->json([
            'status'  => 200,
            'message' => 'success',
            'data'    => $this->dashboardRepo->getTechnicianDirectAppointments($request, $techId),
        ]);
    }

    // =============================
    // Send Direct Appointment for Completion
    // =============================


    public function sendForCompletion(Request $request)
    {
        $request->validate([
            'sales_order_id' => 'required|string',
            'book_id'        => 'required|string',
        ]);

        $result = $this->service->sendForCompletion($request->book_id, $request->sales_order_id);

        $this->userLogService->create(
            userId: Auth::id(),
            action: 'send_for_completion',
            descriptionEn: 'Sent direct appointment for completion',
            descriptionAr: 'تم إرسال الموعد المباشر للإكمال',
            body: [
                'sales_order_id' => $request->sales_order_id,
            ],
            response: $result

        );
        return response()->json($result);
    }


    public function getAllPreMessagesPowerBiOld(PreAppointmentMessagePowerBiRequest $request)
    {
        set_time_limit(200);

        return ($this->dashboardRepo->exportPreMessages($request)['download_url']);
    }


    public function getAllPreMessagesPowerBi(PreAppointmentMessagePowerBiRequest $request)
    {
        $token = (string) \Illuminate\Support\Str::uuid();

        \Illuminate\Support\Facades\Cache::put("export_status:{$token}", ['status' => 'processing'], now()->addHours(2));

        \App\Jobs\GeneratePreMessagesExport::dispatch($request->validated(), $token);

        return response()->json([
            'status'    => 'processing',
            'token'     => $token,
            'check_url' => route('power-bi-messages.export-status', ['token' => $token]),
        ], 202);
    }

    // New endpoint: poll this with the token from above until status is "ready".
    public function exportStatus(string $token)
    {
        $result = \Illuminate\Support\Facades\Cache::get("export_status:{$token}");

        if (!$result) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json($result);
    }


    public function getAllPreMessages(PreAppointmentMessageIndexRequest $request)
    {
        $authUser = Auth::user();

        if (!$authUser->can('view appointments')) {
            return $this->setCode(code: 401)
                ->setData([])
                ->setMessage('You are not authorized to view this appointment.')
                ->send();
        }
        $result = $this->dashboardRepo->getAllPreMessages($request);

        return response()->json([
            'status'  => true,
            'message' => 'Pre appointment messages retrieved successfully.',
            'data'    => [
                'items'      => PreAppointmentMessageResource::collection($result['items']),
                'pagination' => $result['pagination'],
                'counts'     => $result['counts'],
            ],
        ]);
    }

    public function export(Request $request)
    {
        return response()->json($this->dashboardRepo->exportPreMessages($request));
    }

    public function getPreMessageById(int $id)
    {
        $message = $this->dashboardRepo->getPreMessageById($id);

        if (!$message) {
            return response()->json([
                'status'  => false,
                'message' => 'Pre appointment message not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Pre appointment message retrieved successfully.',
            'data'    => new PreAppointmentMessageResource($message),
        ]);
    }

    // =============================
    // Send Pre Appointment Reminder
    // =============================


    public function sendCustomerResponse(Request $request)
    {
        $request->validate([
            'sales_order_id' => 'required|string',
        ]);
        $result = $this->service->sendCustomerResponse(
            $request->sales_order_id
        );
        $this->userLogService->create(
            userId: Auth::id(),
            action: 'send_customer_response',
            descriptionEn: 'Sent customer response to DY',
            descriptionAr: 'تم إرسال رد العميل إلى DY',
            body: [
                'sales_order_id' => $request->sales_order_id,
            ],
            response: $result
        );
        return ($result);
        if (!$result['success']) {
            return response()->json($result, 422);
        }

        return response()->json($result);
    }

    public function sendDyReminders(Request $request)
    {
        $request->validate([
            'sales_orders'   => 'required|array|min:1',
            'sales_orders.*' => 'required|string',
        ]);
        $this->service->runNonCompletedDyRemindersInBackground($request->input('sales_orders', []));
        $this->userLogService->create(
            userId: Auth::id(),
            action: 'send_dy_reminders',
            descriptionEn: 'Sent DY reminders for pre-appointments',
            descriptionAr: 'تم إرسال تذكيرات DY للمواعيد المسبقة',
            body: [
                'sales_orders' => $request->input('sales_orders', []),
            ],
        );
        return response()->json(['success' => true]);
    }

    public function sendCustomerResponsesToDy(Request $request)
    {
        $request->validate([
            'sales_orders'   => 'required|array|min:1',
            'sales_orders.*' => 'required|string',
        ]);
        $this->service->runSendCustomerResponsesCommandInBackground($request->input('sales_orders', []));

        $this->userLogService->create(
            userId: Auth::id(),
            action: 'send_customer_responses_to_dy',
            descriptionEn: 'Sent customer responses to DY in background',
            descriptionAr: 'تم إرسال ردود العملاء إلى DY في الخلفية',
            body: [
                'sales_orders' => $request->input('sales_orders', []),
            ],
        );
        return response()->json([
            'success' => true,
            'message' => 'Command started in background'
        ]);
    }

    // =============================
    // sync Technicians
    // =============================

    public function syncTechnicians()
    {
        $result = $this->service->runSyncTechnicians();
        $this->userLogService->create(
            userId: Auth::id(),
            action: 'sync_technicians',
            descriptionEn: 'Synchronized technicians with external system',
            descriptionAr: 'تمت مزامنة الفنيين مع النظام الخارجي',
            response: $result->getData(true)
        );
        return response()->json($result);
    }



    // =============================
    //  Pre Appointment Messages Export Link
    // =============================


    public function getLastMonthPreMessagesLink()
    {
        $lastMonth = now('Asia/Riyadh')->subMonthNoOverflow()->format('Y-m');

        $existing = \App\Models\PowerBiExportLink::where('month', $lastMonth)->first();

        if ($existing) {
            return response()->json([
                'status'       => true,
                'month'        => $lastMonth,
                'download_url' => $existing->download_url,
                'generated_at' => $existing->generated_at->toIso8601String(),
                'source'       => 'cached',
            ]);
        }

        // Fallback: nothing stored yet for last month — generate it now,
        // synchronously, reusing the exact same logic the daily job uses.
        try {
            $downloadUrl = app(\App\Jobs\GenerateMonthlyPreMessagesExportLink::class)->generate($lastMonth);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to generate export: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status'       => true,
            'month'        => $lastMonth,
            'download_url' => $downloadUrl,
            'generated_at' => now()->toIso8601String(),
            'source'       => 'generated_on_demand',
        ]);
    }


    // =============================
    //  Pre Appointment Messages Export Link
    // =============================


    // public function getCurrentMonthPreMessagesLink(Request $request)
    // {
    //     if ($request->filled('month')) {
    //         if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $request->input('month'))) {
    //             return response()->json([
    //                 'status'  => false,
    //                 'message' => 'month must be in Y-m format, e.g. 2026-07.',
    //             ], 422);
    //         }

    //         $month = $request->input('month');
    //     } else {
    //         $month = now('Asia/Riyadh')->format('Y-m');
    //     }


    //     $existing = \App\Models\PowerBiExportLink::where('month', $month)->first();

    //     if ($existing) {
    //         return $existing->download_url; // Return the cached download URL if it exists

    //         return response()->json([
    //             'status'       => true,
    //             'month'        => $month,
    //             'download_url' => $existing->download_url,
    //             'generated_at' => $existing->generated_at->toIso8601String(),
    //             'source'       => 'cached',
    //         ]);
    //     }

    //     // Fallback: nothing stored yet for this month — generate it now,
    //     // synchronously, reusing the exact same logic the daily job uses.
    //     try {
    //         $downloadUrl = app(\App\Jobs\GenerateMonthlyPreMessagesExportLink::class)->generate($month);
    //     } catch (\Throwable $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Failed to generate export: ' . $e->getMessage(),
    //         ], 500);
    //     }
    //     return $downloadUrl; // Return the newly generated download URL
    //     return response()->json([
    //         'status'       => true,
    //         'month'        => $month,
    //         'download_url' => $downloadUrl,
    //         'generated_at' => now()->toIso8601String(),
    //         'source'       => 'generated_on_demand',
    //     ]);
    // }



    // =============================
    //  Pre Appointment Messages Export Link
    // =============================




    public function getCurrentMonthPreMessagesLink(\Illuminate\Http\Request $request)
    {
        if ($request->filled('month')) {
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $request->input('month'))) {
                return response()->json([
                    'status'  => false,
                    'message' => 'month must be in Y-m format, e.g. 2026-07.',
                ], 422);
            }

            $month = $request->input('month');
        } else {
            $month = now('Asia/Riyadh')->format('Y-m');
        }

        $existing = \App\Models\PowerBiExportLink::where('month', $month)->first();

        if ($existing) {
            return $existing->download_url;
            return response()->json([
                'status'       => true,
                'month'        => $month,
                'download_url' => $existing->download_url,
                'generated_at' => $existing->generated_at->toIso8601String(),
                'source'       => 'cached',
            ]);
        }

        try {
            $downloadUrl = app(\App\Jobs\GenerateMonthlyPreMessagesExportLink::class)->generate($month);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to generate export: ' . $e->getMessage(),
            ], 500);
        }
        return $downloadUrl;
        return response()->json([
            'status'       => true,
            'month'        => $month,
            'download_url' => $downloadUrl,
            'generated_at' => now()->toIso8601String(),
            'source'       => 'generated_on_demand',
        ]);
    }

    // GET /api/power-bi-messages/download?month=YYYY-MM
    public function downloadPreMessagesExport(\Illuminate\Http\Request $request)
    {
        if ($request->filled('month')) {
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $request->input('month'))) {
                return response()->json([
                    'status'  => false,
                    'message' => 'month must be in Y-m format, e.g. 2026-07.',
                ], 422);
            }

            $month = $request->input('month');
        } else {
            $month = now('Asia/Riyadh')->format('Y-m');
        }

        $path = "exports/pre-appointment-messages/pre-appointment-messages-{$month}.xlsx";

        if (!\Illuminate\Support\Facades\Storage::disk('s3')->exists($path)) {
            try {
                app(\App\Jobs\GenerateMonthlyPreMessagesExportLink::class)->generate($month);
            } catch (\Throwable $e) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to generate export: ' . $e->getMessage(),
                ], 500);
            }
        }

        return \Illuminate\Support\Facades\Storage::disk('s3')->response(
            $path,
            "pre-appointment-messages-{$month}.xlsx"
        );
    }


    public function getAllPreMessagesBi()
    {


        $result = $this->dashboardRepo->getYesterdayPreMessages();

        return  response()->json([
            'status'  => true,
            'message' => 'Pre appointment messages retrieved successfully.',
            'data'    => [
                'items'      => PreAppointmentMessageResource::collection($result),
            ],
        ]);
    }
}
