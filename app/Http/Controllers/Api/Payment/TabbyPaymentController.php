<?php

namespace App\Http\Controllers\Api\Payment;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\GetTabbyPaymentRequest;
use App\Jobs\CompleteSuccessPaymentsJob;
use App\Models\Appointment;
use App\Models\AppointmentLine;
use App\Models\AppointmentPayment;
use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use App\Models\TabbyPayment;
use App\Models\TamaraPayment;
use App\Services\CheckCompleteStatus\CheckCompleteService;
use App\Services\DY365\DyService;
use App\Services\Payment\TabbyPaymentSyncService;
use App\Services\Payment\TabbyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Payment\TabbyPaymentVerification;

class TabbyPaymentController extends Controller
{
    use ApiResponseHelper;

    public $dy_service;

    protected $checkCompleteService;
    public function __construct(DyService $dy_service, CheckCompleteService $checkCompleteService)
    {
        $this->dy_service = $dy_service;
        $this->checkCompleteService = $checkCompleteService;
    }

    public function success(Request $request)
    {
        // return $request->all();
        $amount = $request->amount ?? 0;
        $is_single = $request->is_single ?? false;
        $appointment = Appointment::findOrFail($request->appointment_id);
        $tech = $appointment->technician;


        $appPayment = AppointmentPayment::where('appointment_id', $appointment->id)
            ->where('payment_type', 'tabby')
            ->latest()
            ->first();

        $appPayment->status = 'Success';
        $appPayment->payment_reference_id = $request->payment_id;
        $appPayment->save();

        // Get ALL TabbyPayment records for this appointment
        $payment = TabbyPayment::where('appointment_id', $appointment->id)
            ->latest()
            ->first();

        $tabby = new TabbyService();

        if ($payment) {
            $payment->status = 'Success';
            $payment->save();
            // Save payment_id for each sales line payment
            $payment->payment_id = $request->payment_id;
            $payment->save();

            // Retrieve payment status from Tabby
            $pay = $tabby->retrieveTabbyPayment($payment->payment_id);

            if ($pay['status'] === 'AUTHORIZED') {
                $pay = $tabby->capturePaymentRequest(
                    $request->payment_id,
                    $payment->reference_id,
                    $payment->amount
                );
            }

            if ($pay['status'] === 'CLOSED' && isset($pay['captures'])) {
                $appPayment = AppointmentPayment::where('payment_reference_id', $pay['captures'][0]['reference_id'])
                    ->where('payment_type', 'tabby')
                    ->first();

                if ($appPayment) {
                    $appPayment->status = 'CLOSED';
                    $appPayment->payment_reference_id = $request->payment_id;
                    $appPayment->save();
                }

                $payment->status = 'CLOSED';
                $payment->save();
            }

            // Update appointment collect amount
            if ($appointment->collect >= $payment->amount) {
                $appointment->collect -= $appointment->amount;
            }

            if ($appointment->paid < $appointment->total_price) {
                $appointment->paid += $payment->amount;
            }

            $appointment->save();

            $cashPayment = AppointmentPayment::where('appointment_id', $appointment->id)
                ->whereIn('payment_type', ['cash', 'pos']) // cash OR pos
                ->latest()
                ->first();

            $body = [
                "_contract" => [
                    "worker" => $tech->tech_id,
                    "SalesOrderId" => $appointment->sales_order_id,
                    "BookId" => $appointment->book_id,
                    "Discount" => $appointment->discount_value ?? 0,
                    "salesLines" => [
                        [
                            'PaymentReference' => $payment->payment_id
                                ?: ($payment->reference_id ?: null),
                            "TotalAmount" => $payment->amount,
                            "PaymentMethod" => "TABI"
                        ],
                        // want to send cash payment if exists
                        $cashPayment ? [
                            "PaymentReference" => $cashPayment->payment_id
                                ?: ($cashPayment->reference_id ?: null),
                            "TotalAmount" => $cashPayment->total_price,
                            "PaymentMethod"    => strtoupper($cashPayment->payment_type) // CASH or POS
                        ] : null
                    ]
                ]
            ];

            $appointment->update([
                'status' => 'processing'
            ]);


            // CompleteSuccessPaymentsJob::dispatch($appointment, $body);
            $cmd = "php " . escapeshellarg(base_path('artisan')) . " payments:complete "
                . escapeshellarg($appointment->id) . " "
                . escapeshellarg(json_encode($body))
                . " > /dev/null 2>&1 &";

            exec($cmd);
            // try {
            //     $response = $this->dy_service->completeSuccessPayments($body);

            //     // if DyService returns JSON response object
            //     if (is_array($response) || $response instanceof \Illuminate\Support\Arrayable) {
            //         $data = is_array($response) ? $response : $response->toArray();

            //         if (isset($data['Status']) && $data['Status'] === false) {
            //             Log::warning('DyService payment failed', [
            //                 'code' => $data['Code'] ?? 400,
            //                 'error' => $data['Error'] ?? 'Unknown error',
            //                 'appointment_id' => $appointment->id,
            //             ]);
            //         }

            //         // updated completed from dy
            //         if (isset($data['Status']) && $data['Status'] === true) {
            //             $appointment->update([
            //                 'dy_completed' => 1
            //             ]);
            //         }
            //     }
            // } catch (\Throwable $e) {
            //     Log::error('CompleteSuccessPayments failed for appointment ' . $appointment->id . ': ' . $e->getMessage());
            //     // 🚨 Do not return, just continue
            // }

            // Try to get appointment details again to update local DB
            try {
                $this->getAppointmentBySalesOrder($appointment->id);
            } catch (\Throwable $e) {
                // Log::error('getAppointmentBySalesOrder failed: ' . $e->getMessage());
            }
        }
        $appointment->update([
            'status' => 'processing'
        ]);
        $appointment->save();

        // Calculate amounts for view
        $totalAmount = $amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'success',
            'payment_type' => 'tabby',
            'payment' => $payment,
            'phone' => $request->phone ?? $appointment->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }


    public function cancel(Request $request)
    {
        $amount = $request->amount ?? 0;
        // Get appointment & all related TabbyPayments
        $appointment = Appointment::findOrFail($request->appointment_id);
        $payment = TabbyPayment::where('appointment_id', $appointment->id)
            ->latest()
            ->first();


        // Update TabbyPayment
        $payment->payment_id = $request->payment_id ?? $payment->payment_id;
        $payment->status = 'Canceled';
        $payment->save();

        // Update related AppointmentPayment for this sales line
        $tabbyAppPayment = AppointmentPayment::where('appointment_id', $appointment->id)
            ->where('payment_type', 'tabby')
            ->latest()
            ->first();

        if ($tabbyAppPayment) {
            $tabbyAppPayment->status = 'Canceled';
            $tabbyAppPayment->save();
        }


        // Total amount from all canceled payments
        $totalAmount = $amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'canceled',
            'payment_type' => 'tabby',
            'payment' => $payment, // just to display one payment
            // 'phone' => $appointment->phone ?? $payment->reference_id,
            'phone' => $request->phone ?? $appointment->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }


    public function failure(Request $request)
    {
        // Get appointment & all related TabbyPayments
        $amount = $request->amount ?? 0;
        $appointment = Appointment::findOrFail($request->appointment_id);
        $payment = TabbyPayment::where('appointment_id', $appointment->id)
            ->latest()
            ->first();

        // Update TabbyPayment
        $payment->payment_id = $request->payment_id ?? $payment->payment_id;
        $payment->status = 'Failed';
        $payment->save();

        // Update related AppointmentPayment for this sales line
        $tabbyAppPayment = AppointmentPayment::where('appointment_id', $appointment->id)
            ->where('payment_type', 'tabby')
            ->latest()
            ->first();

        if ($tabbyAppPayment) {
            $tabbyAppPayment->status = 'Failed';
            $tabbyAppPayment->save();
        }


        // Calculate totals from all failed payments
        $totalAmount = $amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'failed',
            'payment_type' => 'tabby',
            'payment' => $payment,
            // 'phone' => $appointment->phone ?? $payment->reference_id,
            'phone' => $request->phone ?? $appointment->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }

    public function getPaymentStatus(Request $request)
    {

        $tabby = new TabbyService();
        $sessionPayment = $tabby->retrieveTabbySession($request->session_id);
        $payment_id = $sessionPayment['payment']['id'];

        $tabby_payment = DirectAppointmentPayment::where('reference_id', $request->reference_id)->first();
        $tabby_payment->update([
            'payment_id' => $payment_id,
        ]);

        $tabby = new TabbyService();
        $retrievePayment = $tabby->retrieveTabbyPayment($payment_id);

        if ($retrievePayment['status'] == "AUTHORIZED") {
            $tabby_payment->update([
                'status' => $retrievePayment['status'],
            ]);
            $payment = $tabby->capturePaymentRequest($payment_id, $request->reference_id, $tabby_payment->amount);
            $tabby_payment->update([
                'status' => 'paid',
            ]);
        } else {
            $payment = false;
            $tabby_payment->update([
                'status' => "failed",
            ]);
        }

        // Return a response to acknowledge receipt of the webhook
        return response()->json($tabby_payment);
    }



    public function newSuccess(Request $request)
    {
        $referenceId    = trim((string) $request->reference_id);
        $salesOrderId   = trim((string) $request->sales_order_id);
        $tabbyPaymentId = trim((string) $request->payment_id);

        if ($referenceId === '' || $salesOrderId === '' || $tabbyPaymentId === '') {
            return response()->json([
                'status'  => false,
                'message' => 'reference_id, sales_order_id and payment_id are required.',
            ], 422);
        }

        $directAppointment = DirectAppointment::where('sales_order_id', $salesOrderId)
            ->orderByDesc('id')
            ->first();

        if (!$directAppointment) {
            return response()->json(['status' => false, 'message' => 'DirectAppointment not found'], 404);
        }

        $payment = DirectAppointmentPayment::where('reference_id', $referenceId)->first();

        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Payment not found'], 404);
        }

        // The payment must belong to the order named in the URL.
        if ((string) $payment->sales_order_id !== $salesOrderId) {
            Log::warning('Tabby success: payment does not belong to the given sales order', [
                'reference_id'  => $referenceId,
                'sales_order_id' => $salesOrderId,
            ]);

            return response()->json(['status' => false, 'message' => 'Payment does not belong to this sales order.'], 422);
        }

        // A replay of an already-paid payment: nothing to verify and nothing to change.
        if ($payment->status === 'paid') {
            return $this->tabbyResultView($payment, 'paid');
        }

        // Only a pending payment (or one an earlier cancel/failure callback marked failed) can become paid.
        if (!in_array($payment->status, ['pending', 'failed'], true)) {
            return response()->json(['status' => false, 'message' => 'This payment can no longer be completed.'], 409);
        }

        $tabby = app(TabbyService::class);

        try {
            $check = TabbyPaymentVerification::evaluate(
                $tabby->retrieveTabbyPayment($tabbyPaymentId),
                (string) $payment->reference_id,
                $payment->price
            );

            if ($check['verdict'] === TabbyPaymentVerification::NEEDS_CAPTURE) {
                $tabby->capturePaymentRequest($tabbyPaymentId, $payment->reference_id, $payment->price);

                // Never trust the capture reply alone: read the payment back and check it again.
                $check = TabbyPaymentVerification::evaluate(
                    $tabby->retrieveTabbyPayment($tabbyPaymentId),
                    (string) $payment->reference_id,
                    $payment->price
                );
            }
        } catch (\Throwable $e) {
            // Any retrieval / capture error leaves the payment exactly as it was. It must never become paid.
            Log::error('Tabby success: provider verification failed; payment left unchanged', [
                'reference_id' => $referenceId,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'The payment could not be verified with Tabby. Please try again.',
            ], 502);
        }

        if ($check['verdict'] === TabbyPaymentVerification::MISMATCH) {
            Log::warning('Tabby success: provider payment does not match the local payment', [
                'reference_id' => $referenceId,
                'reason'       => $check['reason'],
            ]);

            return response()->json(['status' => false, 'message' => 'The Tabby payment does not match this payment.'], 422);
        }

        if ($check['verdict'] === TabbyPaymentVerification::REJECTED) {
            DB::transaction(function () use ($payment) {
                $locked = DirectAppointmentPayment::whereKey($payment->id)->lockForUpdate()->first();

                if ($locked && $locked->status === 'pending') {
                    $locked->update(['status' => 'failed']);
                }
            });

            return $this->tabbyResultView($payment->fresh(), 'failed');
        }

        if ($check['verdict'] !== TabbyPaymentVerification::PAID) {
            // Not completed (yet): show the failed page but do not touch the payment.
            return $this->tabbyResultView($payment, 'failed');
        }

        $outcome = DB::transaction(function () use ($payment, $directAppointment, $tabbyPaymentId) {
            $locked = DirectAppointmentPayment::whereKey($payment->id)->lockForUpdate()->first();

            if ($locked->status === 'paid') {
                return 'already_paid';   // a concurrent or replayed request got here first
            }

            if (!in_array($locked->status, ['pending', 'failed'], true)) {
                return 'invalid_state';
            }

            $locked->update(['status' => 'paid', 'payment_id' => $tabbyPaymentId]);

            $appointment = DirectAppointment::whereKey($directAppointment->id)->lockForUpdate()->first();
            $appointment->collect = max(0, (float) $appointment->collect - (float) $locked->price);
            $appointment->save();

            $paidSum  = (float) $appointment->payments()->where('status', 'paid')->sum('price');
            $discount = (float) ($appointment->discount ?? 0);
            $required = (float) ($appointment->required_amount ?? 0);

            if ($required > 0 && abs(($paidSum + $discount) - $required) < 0.01) {
                $appointment->update(['status' => 'paid', 'collect' => 0]);
            }

            return 'paid';
        });

        if ($outcome === 'invalid_state') {
            return response()->json(['status' => false, 'message' => 'This payment can no longer be completed.'], 409);
        }

        return $this->tabbyResultView($payment->fresh(), 'paid');
    }

    private function tabbyResultView(DirectAppointmentPayment $payment, string $status)
    {
        $totalAmount     = (float) $payment->price;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount       = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status'          => $status,
            'payment_type'    => 'tabby',
            'payment'         => $payment,
            'phone'           => $payment->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount'       => $taxAmount,
        ]);
    }
    public function newCancel(Request $request)
    {
        $payment = DirectAppointmentPayment::where('reference_id', $request->reference_id)->first();
        // Update TabbyPayment
        $payment->payment_id = $request->payment_id ?? $payment->payment_id;
        $payment->status = 'failed';
        $payment->save();

        // Total amount from all canceled payments
        $totalAmount = $payment->price;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'canceled',
            'payment_type' => 'tabby',
            'payment' => $payment, // just to display one payment
            // 'phone' => $appointment->phone ?? $payment->reference_id,
            'phone' => $payment->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }


    public function newFailure(Request $request)
    {
        $payment = DirectAppointmentPayment::where('reference_id', $request->reference_id)->first();
        $payment->status = 'failed';
        $payment->save();

        // Calculate totals from all failed payments
        $totalAmount = $payment->price;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'failed',
            'payment_type' => 'tabby',
            'payment' => $payment,
            'phone' => $payment->phone,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }


    public function getPaymentDetails(GetTabbyPaymentRequest $request, TabbyService $tabby)
    {
        $validated = $request->validated();
        $paymentId = $validated['payment_id'] ?? null;

        $localPayment = null;

        if (!$paymentId) {
            $localPayment = \App\Models\DirectAppointmentPayment::where('reference_id', $validated['reference_id'])->first();

            if (!$localPayment) {
                return response()->json([
                    'status'  => false,
                    'message' => "No local payment found for reference_id '{$validated['reference_id']}'.",
                ], 404);
            }

            if (!$localPayment->payment_id) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This payment has no Tabby payment_id yet (checkout may not have completed).',
                    'data'    => ['local_payment' => $localPayment],
                ], 404);
            }

            $paymentId = $localPayment->payment_id;
        }

        try {
            $tabbyPayment = $tabby->retrieveTabbyPayment($paymentId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Failed to retrieve Tabby payment.', [
                'payment_id' => $paymentId,
                'error'      => $e->getMessage(),
            ]);
            $tabbyStatusCode = null;
            if (preg_match('/HTTP (\d+):/', $e->getMessage(), $matches)) {
                $tabbyStatusCode = (int) $matches[1];
            }

            $ourStatusCode = $tabbyStatusCode === 404 ? 404 : 502;

            return response()->json([
                'status'  => false,
                'message' => $tabbyStatusCode === 404
                    ? 'This payment was not found on Tabby.'
                    : 'Failed to retrieve payment from Tabby.',
                'error'   => $e->getMessage(),
            ], $ourStatusCode);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'tabby_payment' => $tabbyPayment,
                'local_payment' => $localPayment, // null if looked up by payment_id directly
            ],
        ]);
    }

    public function syncPaymentStatus(GetTabbyPaymentRequest $request, TabbyPaymentSyncService $syncService)
    {
        $validated = $request->validated();

        $payment = null;

        if (!empty($validated['payment_id'])) {
            $payment = \App\Models\DirectAppointmentPayment::where('payment_id', $validated['payment_id'])->first();
        } else {
            $payment = \App\Models\DirectAppointmentPayment::where('reference_id', $validated['reference_id'])->first();
        }

        if (!$payment) {
            return response()->json([
                'status'  => false,
                'message' => 'No local payment found for the given identifier.',
            ], 404);
        }

        try {
            $result = $syncService->syncPayment($payment);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Manual Tabby payment sync failed.', [
                'payment_id'   => $payment->payment_id,
                'reference_id' => $payment->reference_id,
                'error'        => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to sync payment status.',
                'error'   => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'reference_id' => $payment->reference_id,
                'payment_id'   => $payment->payment_id,
                ...$result,
            ],
        ]);
    }
}
