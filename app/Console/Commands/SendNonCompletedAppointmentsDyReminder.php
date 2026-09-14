<?php

namespace App\Console\Commands;

use App\Models\CompleteIssue;
use App\Models\DirectAppointment;
use Illuminate\Console\Command;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\NewDirectIntegrationController;
use App\Services\Payment\PaymentCompletionValidator;

class SendNonCompletedAppointmentsDyReminder extends Command
{
    protected $signature = 'appointments:send-non-completed-dy-reminders {appointments*}';
    protected $description = 'Send reminders for dy appointments that are not marked as completed in Dy365.';

    public function __construct(protected DyService $dyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $salesOrders = (array) $this->argument('appointments');

        $rows = [];

        foreach ($salesOrders as $salesOrderIdInput) {

            $status = 'SKIPPED';
            $error  = null;

            try {
                $appointment = DirectAppointment::where('sales_order_id', $salesOrderIdInput)
                    ->latest('id')
                    ->first();

                if (!$appointment) {
                    $status = 'NOT_FOUND';
                    $error  = 'Appointment not found';

                    $this->warn("⚠️ Appointment not found for Sales Order ID: {$salesOrderIdInput}");
                    Log::warning('Reminder skipped: appointment not found', [
                        'sales_order_id' => $salesOrderIdInput,
                    ]);

                    $rows[] = [
                        'Sales Order ID' => $salesOrderIdInput,
                        'Status'         => $status,
                        'Error'          => $error,
                    ];
                    continue;
                }

                // =========================
                // ✅ Required amount from Dynamics (SOURCE OF TRUTH)
                // =========================
                $controller = app(NewDirectIntegrationController::class);
                $single = $controller->refSingleAppointmentByBookId($appointment->book_id);
                Log::info('all single data ', ['single' => $single]);
                $dispatcher = app(\App\Services\Payment\PaymentCompletionDispatcher::class);
                $serialPayload = $dispatcher->buildSalesLinesSerial($single);
                Log::info('sales lines data', ['sales_lines' => $serialPayload]);

                if ($single instanceof \Illuminate\Http\JsonResponse) {
                    $single = $single->getData(true);
                }

                $requiredAmount = $single['required_amount'] ?? null;
                if ($requiredAmount === null || $requiredAmount === 'not found') {
                    $status = 'FAILED';
                    $error  = 'Unable to retrieve required amount';

                    $this->saveIssueAndContinue($error, $appointment, $appointment->sales_order_id, $appointment->book_id ?? 'unknown', []);

                    $rows[] = [
                        'Sales Order ID' => $appointment->sales_order_id,
                        'Status'         => $status,
                        'Error'          => $error,
                    ];
                    continue;
                }

                $requiredAmount = (float) $requiredAmount;

                // =========================
                // ✅ Paid payments & salesLines
                // =========================
                $payments = $appointment->payments()
                    ->where('status', 'paid')
                    ->orderBy('id')
                    ->get();

                $salesLines = $payments->map(function ($payment) {
                    $method = strtoupper((string) ($payment->payment_type ?? ''));

                    if ($method === 'TABBY') $method = 'TABI';
                    if ($method === 'TABI')  $method = 'TABI';
                    if ($method === 'E-COMMERCE' || $method === 'ECOMMERCE') $method = 'E-Commerce';

                    return [
                        'PaymentReference' => $payment->payment_id ?: ($payment->reference_id ?: null),
                        'TotalAmount'      => (float) ($payment->price ?? 0),
                        'PaymentMethod'    => $method,
                    ];
                })->values()->toArray();

                // ✅ If no paid payments
                if (empty($salesLines)) {
                    if (abs($requiredAmount) < 0.01) {
                        // required=0 => allow completion with dummy line
                        $salesLines = [[
                            'PaymentReference' => null,
                            'TotalAmount'      => 0.0,   // لو Validator بيرفض 0 خليها 0.01
                            'PaymentMethod'    => 'CASH',
                        ]];

                        // ✅ Reset as requested (like completeAppointment)
                        $appointment->update([
                            'complete_v2_calling' => null,
                            'complete_flag'       => 0,
                        ]);
                    } else {
                        $status = 'SKIPPED';
                        $error  = 'no_paid_payments';

                        Log::warning('DY Reminder skipped: no paid payments', [
                            'appointment_id'  => $appointment->id,
                            'sales_order_id'  => $appointment->sales_order_id,
                            'book_id'         => $appointment->book_id,
                            'required_amount' => $requiredAmount,
                        ]);

                        $rows[] = [
                            'Sales Order ID' => $appointment->sales_order_id,
                            'Status'         => $status,
                            'Error'          => $error,
                        ];
                        continue;
                    }
                }

                // ✅ Build request body
                // IMPORTANT: put salesLines (small s) for validator, and SalesLines for backward compatibility
                // $body = [
                //     '_contract' => [
                //         'worker'       => $appointment->tech_id,
                //         'SalesOrderId' => $appointment->sales_order_id,
                //         'BookId'       => $appointment->book_id,
                //         'Discount'     => (float) ($appointment->discount ?? 0),
                //         'SalesLines'   => $salesLines, // ✅ accept both
                //     ],
                // ];
                $body = [
                    '_contract' => array_merge([
                        'worker'       => $appointment->tech_id ?? null,
                        'SalesOrderId' => $appointment->sales_order_id,
                        'BookId'       => $appointment->book_id,
                        'Discount'     => (float) ($appointment->discount ?? 0),
                        'SalesLines'   => $salesLines,
                    ], $serialPayload),
                ];
                Log::info('Built DY request body', ['body' => $body]);
                // Save body (optional)
                $appointment->update(['dy_body' => json_encode($body, JSON_UNESCAPED_UNICODE)]);

                // =========================
                // ✅ Validation (same style)
                // =========================
                $contract = $body['_contract'] ?? null;
                if (!$contract) {
                    $status = 'FAILED';
                    $error  = 'Missing _contract object';

                    $this->saveIssueAndContinue($error, $appointment, $appointment->sales_order_id, $appointment->book_id ?? 'unknown', $body);

                    $rows[] = [
                        'Sales Order ID' => $appointment->sales_order_id,
                        'Status'         => $status,
                        'Error'          => $error,
                    ];
                    continue;
                }

                $bookId = $contract['BookId'] ?? null;
                if (!$bookId) {
                    $status = 'FAILED';
                    $error  = 'Missing BookId in contract';

                    $this->saveIssueAndContinue($error, $appointment, $appointment->sales_order_id, $appointment->book_id ?? 'unknown', $body);

                    $rows[] = [
                        'Sales Order ID' => $appointment->sales_order_id,
                        'Status'         => $status,
                        'Error'          => $error,
                    ];
                    continue;
                }

                $normalizedSalesLines = $contract['salesLines'] ?? $contract['SalesLines'] ?? null;

                if (!is_array($normalizedSalesLines) || empty($normalizedSalesLines)) {
                    $status = 'FAILED';
                    $error  = 'SalesLines is missing or empty';

                    $this->saveIssueAndContinue($error, $appointment, $appointment->sales_order_id, $bookId, $body);

                    $rows[] = [
                        'Sales Order ID' => $appointment->sales_order_id,
                        'Status'         => $status,
                        'Error'          => $error,
                    ];
                    continue;
                }

                $validation = PaymentCompletionValidator::validate($body);
                if (!$validation['ok']) {
                    $status = 'FAILED';
                    $error  = $validation['reason'];

                    $this->saveIssueAndContinue($error, $appointment, $appointment->sales_order_id, $bookId, $body);

                    $rows[] = [
                        'Sales Order ID' => $appointment->sales_order_id,
                        'Status'         => $status,
                        'Error'          => $error,
                    ];
                    continue;
                }

                // =========================
                // ✅ Amount check vs requiredAmount from Dynamics
                // =========================
                $totalAmount = (float) collect($normalizedSalesLines)->sum(
                    fn($line) => (float) ($line['TotalAmount'] ?? 0)
                );

                $discountVal = (float) ($contract['Discount'] ?? 0);
                $calculatedAmount = $totalAmount + $discountVal;

                if (abs($calculatedAmount - $requiredAmount) > 0.01) {
                    $status = 'SKIPPED';
                    $error  = 'Amount mismatch';

                    $collect = $requiredAmount - $calculatedAmount;

                    CompleteIssue::create([
                        'appointment_id'    => $appointment->id,
                        'sales_order_id'    => $appointment->sales_order_id,
                        'book_id'           => $bookId,
                        'required_amount'   => $requiredAmount,
                        'calculated_amount' => $calculatedAmount,
                        'body'              => json_encode($body, JSON_UNESCAPED_UNICODE),
                        'error_message'     => 'Amount mismatch',
                    ]);

                    $appointment->update([
                        'status'      => 'pending',
                        'collect'     => $collect,
                        'dy_response' => json_encode([
                            'error'             => 'Amount mismatch',
                            'required_amount'   => $requiredAmount,
                            'calculated_amount' => $calculatedAmount,
                            'collect'           => $collect,
                        ], JSON_UNESCAPED_UNICODE),
                    ]);

                    $this->error("⚠️ Amount mismatch for {$appointment->sales_order_id}. Collect = {$collect}");

                    $rows[] = [
                        'Sales Order ID' => $appointment->sales_order_id,
                        'Status'         => $status,
                        'Error'          => $error,
                    ];
                    continue;
                }

                // ✅ Send to DY
                Log::info('📤 DY Reminder sending completeSuccessPaymentsV2', [
                    'appointment_id' => $appointment->id,
                    'sales_order_id' => $appointment->sales_order_id,
                    'book_id'        => $bookId,
                ]);

                $dyResp = $this->dyService->completeSuccessPaymentsV2($body);
                $dyData = is_array($dyResp) ? $dyResp : (array) $dyResp;

                if (($dyData['Status'] ?? false) === true) {
                    $appointment->update([
                        'complete_flag' => 1,
                        'dy_response'   => json_encode($dyData, JSON_UNESCAPED_UNICODE),
                    ]);

                    $status = 'SENT';
                    $this->info("✅ Sent {$appointment->sales_order_id} successfully.");
                } else {
                    $status = 'FAILED';
                    $error  = 'DY returned unsuccessful response';

                    CompleteIssue::create([
                        'appointment_id'    => $appointment->id,
                        'sales_order_id'    => $appointment->sales_order_id,
                        'book_id'           => $bookId,
                        'required_amount'   => $requiredAmount,
                        'calculated_amount' => $calculatedAmount,
                        'body'              => json_encode($body, JSON_UNESCAPED_UNICODE),
                        'error_message'     => 'DyService returned unsuccessful response (reminder)',
                    ]);

                    $appointment->update([
                        'dy_response' => json_encode($dyData, JSON_UNESCAPED_UNICODE),
                    ]);

                    $this->warn("⚠️ DY returned unsuccessful response for {$appointment->sales_order_id}");
                }

                $rows[] = [
                    'Sales Order ID' => $appointment->sales_order_id,
                    'Status'         => $status,
                    'Error'          => $error,
                ];
            } catch (\Throwable $e) {
                $status = 'EXCEPTION';
                $error  = $e->getMessage();

                Log::error('❌ DY Reminder item exception', [
                    'sales_order_id' => $salesOrderIdInput,
                    'error'          => $e->getMessage(),
                ]);

                $this->error("❌ Failed {$salesOrderIdInput}: {$e->getMessage()}");

                $rows[] = [
                    'Sales Order ID' => $salesOrderIdInput,
                    'Status'         => $status,
                    'Error'          => $error,
                ];

                continue;
            }
        }

        $this->table(['Sales Order ID', 'Status', 'Error'], $rows);

        $this->info('🎯 All non-completed appointment reminders processed successfully.');
        return Command::SUCCESS;
    }

    protected function saveIssueAndContinue(
        string $message,
        DirectAppointment $appointment,
        ?string $salesOrderId,
        string $bookId,
        array $body
    ): void {
        Log::warning("⛔ DY Reminder stopped for item", [
            'appointment_id' => $appointment->id,
            'sales_order_id' => $salesOrderId,
            'book_id'        => $bookId,
            'reason'         => $message,
        ]);

        CompleteIssue::create([
            'appointment_id'    => $appointment->id,
            'sales_order_id'    => $salesOrderId,
            'book_id'           => $bookId,
            'required_amount'   => null,
            'calculated_amount' => null,
            'body'              => json_encode($body, JSON_UNESCAPED_UNICODE),
            'error_message'     => $message,
        ]);

        $appointment->update([
            'status'      => 'pending',
            'dy_response' => json_encode([
                'error'  => $message,
                'reason' => 'forced_stop',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->error("❌ {$salesOrderId}: {$message}");
    }
}
