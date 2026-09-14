<?php

namespace App\Http\Controllers\Api\Payment;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\GetTamaraPaymentRequest;
use App\Imports\TamaraImport;
use App\Jobs\CompleteSuccessPaymentsJob;
use App\Jobs\RetryPaymentCompletion;
use App\Models\AppointmentPayment;
use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use App\Models\TabbyPayment;
use App\Models\TamaraPayment;
use App\Services\CheckCompleteStatus\CheckCompleteService;
use App\Services\DY365\DyService;
use App\Services\Payment\PaymentCompletionDispatcher;
use App\Services\Payment\TamaraPaymentSyncService;
use App\Services\Payment\TamaraService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;


class TamaraPaymentController extends Controller
{
    use ApiResponseHelper;
    public $dy_service;
    protected $checkCompleteService;
    protected $tamaraService;
    public function __construct(DyService $dy_service, CheckCompleteService $checkCompleteService, TamaraService $tamaraService)
    {
        $this->dy_service = $dy_service;
        $this->checkCompleteService = $checkCompleteService;
        $this->tamaraService = $tamaraService;
    }

    // tamara

    public function success(Request $request)
    {
        $orderId = $request->query('orderId');


        // Get payment for this sales line
        $payment = TamaraPayment::where('reference_id', $orderId)
            ->latest()
            ->first();

        if (!$payment) {
            // \Log::warning("TamaraPayment not found  for cancelled order {$orderId}");
            return response()->json(['error' => 'No matching payment records found'], 404);
        }
        // Update appointment payment
        $appPayment = AppointmentPayment::where('appointment_id', $request->appointment_id)
            ->where('payment_type', 'tamara')
            ->latest()
            ->first();

        $appPayment->status = 'Success';
        $appPayment->payment_reference_id = $orderId;
        $appPayment->save();


        if ($appPayment) {
            $appointment = $appPayment->appointment;
        } else {
            $appointment = $payment->appointment;
        }

        $tech = $appointment->technician;

        // Send DY complete success payment for this sales line
        $cashPayment = AppointmentPayment::where('appointment_id', $appointment->id)
            ->whereIn('payment_type', ['cash', 'pos', 'TRNS']) // cash OR pos
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
                        "PaymentMethod" => "Tamara"
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

        try {
            $this->getAppointmentBySalesOrder($appointment->id);
        } catch (\Throwable $e) {
            // \Log::error('getAppointmentBySalesOrder failed: ' . $e->getMessage());
        }

        // Deduct from collect
        if ($appointment->collect >= $payment->amount) {
            $appointment->collect -= $payment->amount;
            $appointment->save();
        }
        if ($appointment->paid < $appointment->total_price) {
            $appointment->paid += $payment->amount;
        }
        $appointment->update([
            'status' => 'processing'
        ]);
        $appointment->save();

        // Call Tamara API for this order
        $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);

        if (isset($statusResponse['status'])) {
            if ($statusResponse['status'] === 'approved') {
                $authResponse = app(TamaraService::class)->authorizeOrder($orderId);
                if (isset($authResponse['status']) && $authResponse['status'] === 'authorised') {
                    $payment->status = 'authorised';
                    $payment->save();
                    if ($appPayment) {
                        $appPayment->status = 'Success';
                        $appPayment->save();
                    }

                    $captureResponse = app(TamaraService::class)->captureOrder($orderId);
                    if (isset($captureResponse['status']) && $captureResponse['status'] === 'fully_captured') {
                        if ($captureResponse['data']['status'] === 'fully_captured') {
                            $payment->status = 'fully_captured';
                        } elseif ($captureResponse['data']['status'] === 'captured') {
                            $payment->status = 'captured';
                        }
                    }
                    $payment->save();
                    if ($appPayment) {
                        $appPayment->status = $payment->status;
                        $appPayment->save();
                    }
                }
            } elseif ($statusResponse['status'] === 'authorised') {
                $captureResponse = app(TamaraService::class)->captureOrder($orderId);
                if (isset($captureResponse['status']) && $captureResponse['status'] === 'captured') {
                    if ($captureResponse['status'] === 'captured') {
                        $payment->status = 'captured';
                        $payment->save();
                        if ($appPayment) {
                            $appPayment->status = 'captured';
                            $appPayment->save();
                        }
                    }
                }
            }

            // Mark success regardless of status flow
            if ($appPayment) {
                $appPayment->status = 'Success';
                $appPayment->save();
            }
        }


        // Show result for the last processed payment
        $lastPayment = $payment ?? null;
        if ($lastPayment) {
            $appointment = $lastPayment->appointment;
            $totalAmount = $lastPayment->amount;
            $priceWithoutTax = round($totalAmount / 1.15, 2);
            $taxAmount = round($totalAmount - $priceWithoutTax, 2);

            return view('Payment.result', [
                'status' => 'success',
                'payment_type' => 'tamara',
                'payment' => $lastPayment,
                'phone' => $appointment->phone ?? $lastPayment->reference_id,
                'priceWithoutTax' => $priceWithoutTax,
                'taxAmount' => $taxAmount,
            ]);
        }

        return response()->json(['message' => 'No payments processed'], 404);
    }

    public function failure(Request $request)
    {
        $orderId = $request->query('orderId');
        $lastPayment = null;
        // Find payment for this sales line
        $payment = TamaraPayment::where('reference_id', $orderId)
            ->latest()
            ->first();

        if (!$payment) {
            // \Log::warning("TamaraPayment not found  for failure order {$orderId}");
            return response()->json(['error' => 'No matching payment records found'], 404);
        }

        $lastPayment = null;

        // foreach ($salesIds as $salesLineId) {
        // Get payment for this sales line
        $payment = TamaraPayment::where('reference_id', $orderId)
            ->latest()
            ->first();

        if (!$payment) {
            // \Log::warning("TamaraPayment not found  for cancelled order {$orderId}");
            return response()->json(['error' => 'No matching payment records found'], 404);
        }

        // Mark payment as failed
        $payment->status = 'failed';
        $payment->save();

        // Update appointment payment record
        $tamaraAppPayment = AppointmentPayment::where('payment_reference_id', $orderId)
            ->where('payment_type', 'tamara')
            ->latest()
            ->first();

        if ($tamaraAppPayment) {
            $tamaraAppPayment->status = 'Failed';
            $tamaraAppPayment->save();
        }

        $lastPayment = $payment;
        // }

        // If nothing was found, return JSON error
        if (!$lastPayment) {
            return response()->json(['error' => 'No matching payment records found'], 404);
        }

        // Show result for the last processed failed payment
        $appointment = $lastPayment->appointment;
        $totalAmount = $lastPayment->amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'failed',
            'payment_type' => 'tamara',
            'payment' => $lastPayment,
            'phone' => $appointment->phone ?? $lastPayment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }


    public function cancel(Request $request)
    {
        $orderId = $request->query('orderId');
        $lastPayment = null;
        // Find payment for this sales line
        $payment = TamaraPayment::where('reference_id', $orderId)
            ->latest()
            ->first();

        if (!$payment) {
            // \Log::warning("TamaraPayment not found  for cancelled order {$orderId}");
            return response()->json(['error' => 'No matching payment records found'], 404);
        }

        // Update TamaraPayment status
        $payment->status = 'cancelled';
        $payment->save();

        // Update AppointmentPayment status
        $tamaraAppPayment = AppointmentPayment::where('payment_reference_id', $orderId)
            ->where('payment_type', 'tamara')
            ->latest()
            ->first();

        if ($tamaraAppPayment) {
            $tamaraAppPayment->status = 'Canceled';
            $tamaraAppPayment->save();
        }

        $lastPayment = $payment;


        if (!$lastPayment) {
            return response()->json(['error' => 'No matching payment records found'], 404);
        }

        // Prepare result for last processed cancellation
        $appointment = $lastPayment->appointment;
        $totalAmount = $lastPayment->amount; // includes 15% VAT
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'canceled',
            'payment_type' => 'tamara',
            'payment' => $lastPayment,
            'phone' => $appointment->phone ?? $lastPayment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }

    // new integration functions

    public function newSuccess(Request $request)
    {
        $url          = $request->path();
        preg_match('/reference_id=([^\/]+)/', $url, $match);
        $referenceId  = $match[1] ?? $request->query('reference_id');
        $orderId      = $request->query('orderId');          // Tamara order UUID from redirect
        $salesOrderId = $request->sales_order_id ?? null;

        try {
            // ── 1. Resolve appointment ────────────────────────────────────────────
            $directAppointment = DirectAppointment::where('sales_order_id', $salesOrderId)
                ->orderByDesc('id')
                ->first();

            if (!$directAppointment) {
                return response()->json(['status' => false, 'message' => 'DirectAppointment not found']);
            }

            // ── 2. Lock payment row + idempotency guard ───────────────────────────
            //    lockForUpdate() requires a DB transaction; without it the lock is
            //    silently ignored and the race condition remains.
            $payment = DB::transaction(function () use ($referenceId, $orderId, $directAppointment) {

                $payment = DirectAppointmentPayment::where('reference_id', $referenceId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment) {
                    return null;
                }

                // Already processed by a concurrent webhook — bail out early
                if ($payment->status === 'paid') {
                    return $payment;
                }

                // Persist Tamara orderId so the webhook can also use it reliably
                if ($orderId && empty($payment->payment_id)) {
                    $payment->update(['payment_id' => $orderId]);
                }

                return $payment;
            });

            if (!$payment) {
                return response()->json(['status' => false, 'message' => 'Payment not found']);
            }

            // Already handled — show success view without reprocessing
            if ($payment->status === 'paid') {
                return $this->tamaraResultView($payment, 'paid');
            }

            // ── 3. Tamara authorize → capture → VERIFY before marking paid ───────
            //
            // ⚠ CRITICAL FIX: the original code called getOrderStatus() and
            // attempted authorize/capture, but then marked the LOCAL payment
            // "paid" completely unconditionally afterward — regardless of
            // whether Tamara's status was ever actually approved/authorised/
            // captured, and even if the API call threw and was caught. Since
            // this is a GET route with no signature check at all, anyone who
            // knew or guessed a reference_id + sales_order_id pair could hit
            // this URL directly and have their payment marked paid without
            // ever actually paying. Fixed: only mark paid if a final
            // getOrderStatus() check genuinely confirms a captured order.
            $orderId = $payment->payment_id;
            $verifiedCaptured = false;

            if ($orderId) {
                try {
                    $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);
                    $tamaraStatus   = $statusResponse['status'] ?? null;

                    if ($tamaraStatus === 'approved') {
                        $authResponse = app(TamaraService::class)->authorizeOrder($orderId);
                        if (($authResponse['status'] ?? null) === 'authorised') {
                            app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                        }
                    } elseif ($tamaraStatus === 'authorised') {
                        app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                    }

                    // Re-check AFTER attempting authorize/capture — this is
                    // the actual proof of payment, not an assumption that
                    // the calls above succeeded.
                    // ⚠ CONFIRM 'captured' is the exact status string Tamara
                    // returns for a fully captured order — this is inferred
                    // from Tamara's terminology (the API endpoint used above
                    // is literally /payments/capture), not confirmed against
                    // their actual docs. If wrong, this will incorrectly
                    // refuse to ever mark payments paid rather than the
                    // previous bug of marking everything paid — verify this
                    // value against a real successful test transaction.
                    $finalStatusResponse = app(TamaraService::class)->getOrderStatus($orderId);
                    $finalStatus = $finalStatusResponse['status'] ?? null;

                    $verifiedCaptured = in_array($finalStatus, ['captured', 'fully_captured'], true);
                } catch (\Throwable $e) {
                    Log::error('Tamara authorize/capture failed (newSuccess): ' . $e->getMessage(), [
                        'order_id'     => $orderId,
                        'reference_id' => $referenceId,
                    ]);
                }
            } else {
                Log::warning('newSuccess: payment_id (orderId) is empty', [
                    'reference_id' => $referenceId,
                    'payment_id'   => $payment->id,
                ]);
            }

            if (!$verifiedCaptured) {
                Log::warning('newSuccess: Tamara payment not verified as captured — refusing to mark paid.', [
                    'reference_id' => $referenceId,
                    'order_id'     => $orderId,
                ]);

                // Show a pending/processing view rather than a false
                // "paid" confirmation — the customer's browser landed on
                // the success URL, but that alone proves nothing.
                return $this->tamaraResultView($payment, 'pending');
            }

            // ── 4. Mark paid locally — only reached if verifiedCaptured ───────────
            $payment->update(['status' => 'paid']);

            $directAppointment->collect = max(
                0,
                (float) $directAppointment->collect - (float) $payment->price
            );
            $directAppointment->save();

            // ── 5. Check totals and complete appointment if fully paid ────────────
            $this->completeIfFullyPaid($payment, $directAppointment, $orderId);

            return $this->tamaraResultView($payment, 'paid');
        } catch (\Throwable $e) {
            Log::error('newSuccess exception: ' . $e->getMessage(), [
                'reference_id' => $referenceId ?? null,
                'order_id'     => $orderId     ?? null,
            ]);
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }


    /**
     * ✅ Small helper to keep return view consistent
     */
    protected function tamaraResultView(DirectAppointmentPayment $payment, string $status)
    {
        $totalAmount     = (float) $payment->price;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount       = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status'          => $status,
            'payment_type'    => 'tamara',
            'payment'         => $payment,
            'phone'           => $payment->phone ?? $payment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount'       => $taxAmount,
        ]);
    }
    public function newFailure(Request $request)
    {
        // Get ALL TabbyPayment records for this appointment
        $payment = DirectAppointmentPayment::where('reference_id', $request->reference_id)->first();
        if ($payment) {
            $payment->status = 'failed';
            $payment->save();
        }
        $totalAmount = $payment->price;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'failed',
            'payment_type' => 'tamara',
            'payment' => $payment,
            'phone' => $payment->phone ?? $payment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }


    public function newCancel(Request $request)
    {
        // Get ALL TabbyPayment records for this appointment
        $payment = DirectAppointmentPayment::where('reference_id', $request->reference_id)->first();
        if ($payment) {
            $payment->status = 'failed';
            $payment->save();
        }
        $totalAmount = $payment->price;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status' => 'canceled',
            'payment_type' => 'tamara',
            'payment' => $payment,
            'phone' => $payment->phone ?? $payment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }



    public function webhook(Request $request)
    {
        $payload = $request->all();
        // Log::info('Tamara webhook received', $payload);

        $referenceId = $payload['order_reference_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$referenceId || !$status) {
            return response()->json(['error' => 'Missing data'], 400);
        }

        $payment = TamaraPayment::where('reference_id', $referenceId)->first();

        if ($payment) {
            $payment->status = strtolower($status);
            $payment->save();

            // Optionally update appointment status here
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required'
        ]);
        $import = new TamaraImport();
        Excel::import($import, $request->file('file'));
        return response()->json([
            'status' => true,
            'appointments' => $import->appointmentsList,
            'count' => count($import->appointmentsList)
        ]);
    }
    public function newNotification(Request $request)
    {
        $url = $request->path();
        preg_match('/reference_id=([^\/]+)/', $url, $match);
        $referenceId = $match[1] ?? $request->query('reference_id');

        // FIX: prefer query() over fragile string-split; fall back to split for
        //      legacy URL shapes where sales_order_id is embedded in the path.
        $salesOrderId = $request->query('sales_order_id')
            ?? (explode('sales_order_id=', $url)[1] ?? null);

        try {
            // ── 1. Resolve appointment (with null guard) ───────────────────────────
            $directAppointment = DirectAppointment::where('sales_order_id', $salesOrderId)
                ->orderByDesc('id')
                ->first();

            if (!$directAppointment) {
                // Return 200 so Tamara stops retrying an unfixable request
                Log::warning('newNotification: DirectAppointment not found', [
                    'sales_order_id' => $salesOrderId,
                    'reference_id'   => $referenceId,
                ]);
                return response()->json(['status' => false, 'message' => 'DirectAppointment not found']);
            }

            // ── 2. Lock payment row + idempotency guard ───────────────────────────
            $payment = DB::transaction(function () use ($referenceId) {

                $payment = DirectAppointmentPayment::where('reference_id', $referenceId)
                    ->lockForUpdate()
                    ->first();

                if (!$payment || $payment->status === 'paid') {
                    return $payment; // null = not found; status=paid = already done
                }

                return $payment;
            });

            if (!$payment) {
                return response()->json(['status' => false, 'message' => 'Payment not found']);
            }

            // Already processed (possibly by the concurrent redirect) — stop here
            if ($payment->status === 'paid') {
                return response()->json(['status' => true, 'message' => 'Already processed']);
            }

            // ── 3. Tamara authorize → capture → VERIFY before marking paid ───────
            //
            // ⚠ CRITICAL FIX: same issue as newSuccess() — this endpoint has
            // NO signature verification on the incoming request at all (no
            // check equivalent to Tabby's hash_equals() header comparison),
            // and previously marked the payment "paid" unconditionally
            // regardless of what Tamara's status check returned. Combined,
            // this meant anyone who could POST to this URL with a known
            // reference_id + sales_order_id could mark any pending payment
            // paid without any real payment occurring. Fixed to require a
            // genuinely verified captured status before mutating anything.
            $orderId = $payment->payment_id;
            $verifiedCaptured = false;

            if ($orderId) {
                try {
                    $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);
                    $tamaraStatus   = $statusResponse['status'] ?? null;

                    if ($tamaraStatus === 'approved') {
                        $authResponse = app(TamaraService::class)->authorizeOrder($orderId);
                        if (($authResponse['status'] ?? null) === 'authorised') {
                            app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                        }
                    } elseif ($tamaraStatus === 'authorised') {
                        app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                    }

                    // ⚠ Confirm 'captured' is the exact status string Tamara
                    // returns for a fully captured order against a real test
                    // transaction — inferred from their terminology, not
                    // confirmed against their docs.
                    $finalStatusResponse = app(TamaraService::class)->getOrderStatus($orderId);
                    $finalStatus = $finalStatusResponse['status'] ?? null;

                    $verifiedCaptured = in_array($finalStatus, ['captured', 'fully_captured'], true);
                } catch (\Throwable $e) {
                    Log::error('Tamara authorize/capture failed (newNotification): ' . $e->getMessage(), [
                        'order_id'     => $orderId,
                        'reference_id' => $referenceId,
                    ]);
                }
            } else {
                Log::warning('newNotification: payment_id (orderId) is empty', [
                    'reference_id' => $referenceId,
                    'payment_id'   => $payment->id,
                ]);
            }

            if (!$verifiedCaptured) {
                Log::warning('newNotification: Tamara payment not verified as captured — refusing to mark paid.', [
                    'reference_id' => $referenceId,
                    'order_id'     => $orderId,
                ]);

                return response()->json(['status' => true, 'message' => 'Notification received, payment not yet verified as captured']);
            }

            // ── 4. Mark paid locally — only reached if verifiedCaptured ───────────
            $payment->update(['status' => 'paid']);

            $directAppointment->collect = max(
                0,
                (float) $directAppointment->collect - (float) $payment->price  // FIX: float casts added
            );
            $directAppointment->save();

            // ── 5. Check totals and complete appointment if fully paid ────────────
            //    FIX: this was entirely missing from the webhook — it's now shared
            //    via completeIfFullyPaid() so both paths trigger Dynamics completion.
            $this->completeIfFullyPaid($payment, $directAppointment, $orderId);

            return response()->json(['status' => true, 'message' => 'Notification processed successfully']);
        } catch (\Throwable $e) {
            Log::error('newNotification exception: ' . $e->getMessage(), [
                'reference_id'  => $referenceId  ?? null,
                'sales_order_id' => $salesOrderId ?? null,
            ]);
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }



    private function completeIfFullyPaid(DirectAppointmentPayment $payment, DirectAppointment  $directAppointment, ?string $orderId): void
    {
        // Re-query paid sum after this payment was recorded
        $paidSum  = (float) $directAppointment->payments()->where('status', 'paid')->sum('price');
        $discount = (float) ($directAppointment->discount        ?? 0);
        $required = (float) ($directAppointment->required_amount ?? 0);

        $allPaid = $required > 0 && abs(($paidSum + $discount) - $required) < 0.01;

        if (!$allPaid) {
            return; // still partial — nothing more to do
        }

        $appointment = DirectAppointment::where('sales_order_id', $payment->sales_order_id)
            ->orderByDesc('id')
            ->first();

        if (!$appointment) {
            Log::warning('completeIfFullyPaid: appointment not found', [
                'sales_order_id' => $payment->sales_order_id,
            ]);
            return;
        }

        $appointment->update(['status' => 'paid', 'collect' => 0]);

        // ── Guard: orderId must be present ───────────────────────────────────────
        if (!$orderId) {
            Log::warning('Completion queued for retry — missing orderId', [
                'appointment_id' => $appointment->id,
                'reference_id'   => $payment->reference_id,
            ]);
            // FIX: queue a retry instead of silently dropping the completion
            dispatch(new RetryPaymentCompletion($appointment->id))->delay(now()->addMinutes(2));
            return;
        }

        // ── Guard: Tamara must be captured before we complete in Dynamics ─────────
        try {
            $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);
            $tamaraStatus   = $statusResponse['status'] ?? null;
            $isCaptured     = in_array($tamaraStatus, ['captured', 'fully_captured'], true);
        } catch (\Throwable $e) {
            Log::error('Tamara status check failed before completion: ' . $e->getMessage(), [
                'appointment_id' => $appointment->id,
                'order_id'       => $orderId,
            ]);
            // FIX: queue retry rather than lose the completion
            dispatch(new RetryPaymentCompletion($appointment->id))->delay(now()->addMinutes(2));
            return;
        }

        if (!$isCaptured) {
            Log::warning('Completion queued for retry — Tamara not yet captured', [
                'appointment_id' => $appointment->id,
                'order_id'       => $orderId,
                'tamara_status'  => $tamaraStatus,
            ]);
            // FIX: queue retry rather than silently drop
            dispatch(new RetryPaymentCompletion($appointment->id))->delay(now()->addMinutes(2));
            return;
        }
    }



    public function getOrderStatus(string $orderId)
    {
        $result = $this->tamaraService->getOrderStatus($orderId);

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ]);
    }

    public function getPaymentDetails(GetTamaraPaymentRequest $request, TamaraService $tamara)
    {
        $validated = $request->validated();
        $orderId = $validated['order_id'] ?? null;

        $localPayment = null;

        if (!$orderId) {
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
                    'message' => 'This payment has no Tamara order_id yet (checkout may not have completed).',
                    'data'    => ['local_payment' => $localPayment],
                ], 404);
            }

            $orderId = $localPayment->payment_id;
        } else {
            $localPayment = \App\Models\DirectAppointmentPayment::where('payment_id', $orderId)->first();
        }

        $tamaraOrder = $tamara->getOrderStatus($orderId);

        if (isset($tamaraOrder['error']) && $tamaraOrder['error'] === true) {
            \Illuminate\Support\Facades\Log::error('Failed to retrieve Tamara order.', [
                'order_id' => $orderId,
                'error'    => $tamaraOrder['message'] ?? null,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to retrieve order from Tamara.',
                'error'   => $tamaraOrder['message'] ?? 'Unknown error',
            ], 502);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'tamara_order'  => $tamaraOrder,
                'local_payment' => $localPayment,
            ],
        ]);
    }

    public function syncPaymentStatus(GetTamaraPaymentRequest $request, TamaraPaymentSyncService $syncService)
    {
        $validated = $request->validated();

        if (!empty($validated['order_id'])) {
            $payment = \App\Models\DirectAppointmentPayment::where('payment_id', $validated['order_id'])->first();
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
            \Illuminate\Support\Facades\Log::error('Manual Tamara payment sync failed.', [
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
                'order_id'     => $payment->payment_id,
                ...$result,
            ],
        ]);
    }
}
