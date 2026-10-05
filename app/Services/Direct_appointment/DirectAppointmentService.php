<?php

namespace App\Services\Direct_appointment;

use App\Models\DirectAppointment;
use Throwable;
use App\Services\DY365\DyService;
use App\Services\WhatsApp\CustomerResponseRequest;
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
            $message = $this->repo->getLatestBySalesOrder($salesOrder);

            if (!$message) {
                return [
                    'success' => false,
                    'message' => 'Appointment not found'
                ];
            }

            $customerResponse = $message->customer_response;

            if ($customerResponse == "pending") {
                return [
                    'success' => false,
                    'message' => 'Customer has no response to process'
                ];
            }

            try {
                $requestBody = CustomerResponseRequest::body(
                    $message->book_id,
                    $message->sales_order,
                    (string) $customerResponse
                );
            } catch (\InvalidArgumentException $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }

            Log::channel('whatsapp')->info('📤 Sending request to Dy365', [
                'sales_order' => $salesOrder,
                'request'     => $requestBody,
            ]);

            // This runs inside a dashboard request, so it gets ONE attempt limited to what a web
            // request can wait for. It used to wait up to 500s x 3 retries — resending a change
            // request DY may already have processed — and reported success even when DY answered
            // Status:false. The result is now recorded on the message exactly like the WhatsApp
            // webhook and dy:send-customer-responses record theirs.
            $outcome = $this->dyService->submitCustomerChangeRequestWithin($requestBody, 55);
            $record  = CustomerResponseRequest::record($outcome);

            $message->update([
                'flag'        => $record['flag'],
                'dy_response' => json_encode($record['dy_response'], JSON_UNESCAPED_UNICODE),
            ]);

            if ($record['flag'] !== 1) {
                Log::channel('whatsapp')->error('❌ Dy365 did not confirm the customer response', [
                    'sales_order' => $salesOrder,
                    'dy_response' => $record['dy_response'],
                ]);

                return [
                    'success' => false,
                    'message' => $this->customerResponseFailureMessage($record['dy_response']),
                    'dy'      => $record['dy_response'],
                ];
            }

            Log::channel('whatsapp')->info('📥 Response received from Dy365', [
                'sales_order' => $salesOrder,
                'response'    => $record['dy_response'],
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

    /** What to tell the dashboard user when DY did not confirm the customer response. */
    private function customerResponseFailureMessage(array $dyResponse): string
    {
        return match ($dyResponse['outcome'] ?? null) {
            'unconfirmed' => 'The request was sent to Dynamics but no confirmation arrived in time — check Dynamics before sending it again.',
            'not_sent'    => 'Could not reach Dynamics — nothing was sent, it is safe to try again.',
            default       => $dyResponse['error']['Message']
                ?? $dyResponse['error']['Error']
                ?? $dyResponse['data']['Error']
                ?? $dyResponse['data']['Message']
                ?? 'Dy365 rejected the request',
        };
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
