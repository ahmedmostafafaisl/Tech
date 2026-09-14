<?php

namespace App\Services\Direct_appointment;

use App\Models\DirectAppointment;
use Throwable;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use App\Services\CheckCompleteStatus\CheckCompleteService;
use App\Repositories\Interfaces\DashboardRepositoryInterface;

class DirectAppointmentService
{
    protected $repo;
    protected $checkCompleteService;
    protected $dyService;



    public function __construct(
        DashboardRepositoryInterface $repo,
        CheckCompleteService $checkCompleteService,
        DyService $dyService

    ) {
        $this->repo = $repo;
        $this->checkCompleteService = $checkCompleteService;
        $this->dyService = $dyService;
    }



    public function sendForCompletion(string $book_id, string $salesOrderId)
    {
        // $appointment = $this->repo->getLatestBySalesOrderId($salesOrderId);
        $appointment = DirectAppointment::where('book_id', $book_id)->first(); // refetch to ensure we have a model instance
        $appointment = $appointment?->fresh(); // ensure we have the latest data

        if (!$appointment) {
            return [
                'status'  => false,
                'message' => 'No direct appointment found for this sales order ID.'
            ];
        }
        if ($appointment) {
            if ($appointment) {
                $appointment->update([
                    'complete_flag' => 0,
                    'complete_v2_calling' => null,
                    'dy_response' => null,
                    'dy_body' => null,
                ]);
            }
        }

        $dispatcher = app(\App\Services\Payment\PaymentCompletionDispatcher::class);
        $result = $dispatcher->dispatch($appointment);

        // ✅ السيرفيس ممكن ترجع skipped (already_completed / already_triggered)
        if (($result['ok'] ?? false) === true) {
            if (($result['skipped'] ?? false) === true) {
                return [
                    'status'  => true,
                    'message' => 'Completion skipped.',
                    'reason'  => $result['reason'] ?? null,
                    'marker'  => $result['marker'] ?? null,
                ];
            }

            return [
                'status'  => true,
                'message' => 'Appointment sent for completion successfully.',
            ];
        }

        return [
            'status'  => false,
            'message' => 'Payment completion blocked or failed.',
            'reason'  => $result['reason'] ?? 'unknown',
            'result'  => $result,
        ];
    }
    public function sendCustomerResponse(string $salesOrder): array
    {
        try {
            $appointment = $this->repo->getLatestBySalesOrder($salesOrder);
            $customerResponse = $appointment?->customer_response;

            if (!$appointment) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found'
                ];
            }

            if ($customerResponse == "pending") {
                return [
                    'success' => false,
                    'message' => 'Customer has no response to process'
                ];
            }
            $requestBody = [
                '_contract' => [
                    'bookId'        => $appointment->book_id,
                    'salesOrderId'  => $appointment->sales_order,
                    'actionOwner'   => 2,
                ]
            ];

            match ($customerResponse) {
                'confirm'    => $requestBody['_contract']['requestType'] = 2,
                'cancel'     => $requestBody['_contract']['requestType'] = 0,
                'reschedule' => $requestBody['_contract']['requestType'] = 1,
            };

            Log::channel('whatsapp')->info('📤 Sending request to Dy365', [
                'sales_order' => $salesOrder,
                'request'     => $requestBody,
            ]);

            $response = $this->dyService->sendRequest3(
                'post',
                $this->dyService->customerChangeRequest,
                $requestBody
            );

            if ($response['ok'] === false) {

                return [
                    'success' => false,
                    'message' => $response['error']['Message']
                        ?? 'Dy365 rejected the request',
                    'dy'      => $response['error'],
                ];
            }

            Log::channel('whatsapp')->info('📥 Response received from Dy365', [
                'sales_order' => $salesOrder,
                'response'    => is_array($response) ? $response : $response->json(),
            ]);

            return [
                'success' => true,
                'message' => 'Customer response processed successfully'
            ];
        } catch (Throwable $e) {

            Log::channel('whatsapp')->error('❌ Customer response failed', [
                'sales_order' => $salesOrder,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    public function runNonCompletedDyRemindersInBackground(array $salesOrders): void
    {
        $salesOrders = array_values(array_filter(array_map('trim', $salesOrders)));

        if (empty($salesOrders)) {
            Log::warning('appointments:send-non-completed-dy-reminders skipped (empty sales orders)');
            return;
        }

        $args = implode(' ', array_map('escapeshellarg', $salesOrders));

        $bgLog = storage_path('logs/dy_reminders_bg.log');

        $cmd = "php " . escapeshellarg(base_path('artisan'))
            . " appointments:send-non-completed-dy-reminders "
            . $args
            . " >> " . escapeshellarg($bgLog) . " 2>&1 &";

        Log::info('📤 Running DY reminders command in background', [
            'cmd'         => $cmd,
            'count'       => count($salesOrders),
            'sales_orders' => $salesOrders,
            'bg_log'      => $bgLog,
        ]);

        exec($cmd);
    }


    public function runSendCustomerResponsesCommandInBackground(array $salesOrders): void
    {
        $salesOrders = array_values(array_filter(array_map('trim', $salesOrders)));

        if (empty($salesOrders)) {
            Log::warning('dy:send-customer-responses skipped (empty sales_orders)');
            return;
        }

        $args = implode(' ', array_map('escapeshellarg', $salesOrders));

        $cmd = "php " . escapeshellarg(base_path('artisan'))
            . " dy:send-customer-responses "
            . $args
            . " > /dev/null 2>&1 &";

        Log::channel('whatsapp')->info('🚀 Running dy:send-customer-responses in background', [
            'cmd' => $cmd,
            'count' => count($salesOrders),
        ]);

        exec($cmd);
    }

    public function runSyncTechnicians()
    {
        // optional: prevent double run (simple lock file)
        $lockFile = storage_path('app/sync_technicians.lock');
        if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 60 * 10) {
            return response()->json([
                'status' => false,
                'message' => 'Sync already running (lock active).'
            ], 400);
        }
        @file_put_contents($lockFile, now()->toDateTimeString());

        // ✅ Run command in background and log output to file
        $logFile = storage_path('logs/sync_technicians.log');

        $cmd = "php " . escapeshellarg(base_path('artisan'))
            . " dynamics:sync-technicians"
            . " >> " . escapeshellarg($logFile) . " 2>&1 &";

        Log::info('Run sync technicians cmd', ['cmd' => $cmd]);

        exec($cmd);

        return response()->json([
            'status' => true,
            'message' => 'Technician sync started in background.',
            'log' => basename($logFile),
        ], 202);
    }
}
