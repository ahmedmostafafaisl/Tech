<?php

namespace App\Http\Controllers\Api\Payment;

use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\DyPaymentLink;
use App\Models\ClickPayPayment;
use App\Models\DirectAppointment;
use App\Services\DY365\DyService;
use App\Models\AppointmentPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\DirectAppointmentPayment;
use App\Services\Payment\ClickPayService;
use App\Services\DY365\DyAcknowledgement;
use App\Services\Payment\ClickPayPaymentSyncService;
use App\Http\Controllers\Api\DY365\DyController;
use App\Services\CheckCompleteStatus\CheckCompleteService;

class ClickPayController extends Controller
{
    protected ClickPayService $clickPay;
    public $dy_service;
    protected $checkCompleteService;

    public function __construct(ClickPayService $clickPay, DyService $dy_service, CheckCompleteService $checkCompleteService)
    {
        $this->clickPay = $clickPay;
        $this->dy_service = $dy_service;
        $this->checkCompleteService = $checkCompleteService;
    }

    /**
     * Initiate a ClickPay payment
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'amount'         => 'required|numeric|min:0.01',
        ]);

        $appointment = Appointment::with('customer', 'address')->findOrFail($validated['appointment_id']);
        $user = $appointment->customer;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment does not have a valid customer.'
            ], 422);
        }

        $customer = [
            'name'    => $user->username ?? 'Customer',
            'email'   => $user->email ?? 'no-reply@example.com',
            'phone'   => $user->phone ?? '0500000000',
            'city'    => optional($appointment->address)->city ?? 'Riyadh',
            'country' => 'SA',
        ];

        $description = "Payment for Appointment #{$appointment->id}";

        $payload = [
            'amount'      => $validated['amount'],
            'description' => $description,
            'customer'    => $customer,
        ];

        $response = $this->clickPay->createInvoice($appointment, $validated['amount'],  $user->phone, 1);

        if ($response['success'] && isset($response['redirect_url'])) {
            return response()->json([
                'success'     => true,
                'redirect_url' => $response['redirect_url'],
                'transaction' => $response['transaction'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Payment initiation failed.',
            'details' => $response['response'] ?? [],
        ], 422);
    }


    /**
     * Refund a transaction
     */
    public function refund(Request $request)
    {
        $request->validate([
            'tran_ref' => 'required|string',
            'amount'   => 'required|numeric|min:0.01',
            'reason'   => 'nullable|string|max:255',
        ]);

        $tranRef = $request->tran_ref;
        $amount = $request->amount;
        $reason = $request->reason ?? 'Customer refund';

        $response = $this->clickPay->refund($tranRef, $amount, $reason);

        if (isset($response['response_code']) && $response['response_code'] === '000') {
            return response()->json([
                'success' => true,
                'message' => 'Refund successful.',
                'response' => $response
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Refund failed.',
            'response' => $response
        ], 422);
    }

    /**
     * ClickPay return URL handler
     */
    public function handleReturn(Request $request)
    {

        $appointment_id = $request->input('reference_id');
        $paymentMethod  = $request->input('payment_type');
        $type           = $request->input('type');

        if ($type !== 'appointment') {
            return response()->json(['status' => 'error', 'message' => 'Unsupported type.'], 400);
        }

        $appointment = Appointment::find($appointment_id);
        if (!$appointment) {
            return response()->json(['status' => 'error', 'message' => 'Appointment not found.'], 404);
        }

        $results = [];


        $payment = ClickPayPayment::where('appointment_id', $appointment_id)
            ->latest()
            ->first();

        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }



        $clickAppPayment = AppointmentPayment::where('appointment_id', $appointment_id)
            ->where('payment_type', 'clickpay')
            ->latest()
            ->first();

        $clickAppPayment->status = 'Success';
        $clickAppPayment->payment_reference_id = $payment->reference_id;
        $clickAppPayment->save();


        $totalAmount     = $payment->amount ?? $clickAppPayment->total_price;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount       = round($totalAmount - $priceWithoutTax, 2);

        $data = $this->clickPay->handleReturn($payment->reference_id);

        if (
            isset($data['payment_result']['response_message']) &&
            $data['payment_result']['response_message'] === 'Authorised'
        ) {
            $payment->update(['status' => 'success']);
            if ($clickAppPayment) {
                $clickAppPayment->update(['status' => 'Success']);
            }

            if ($appointment->collect >= $payment->amount) {
                $appointment->collect -= $payment->amount;
                $appointment->save();
            }
            if ($appointment->paid < $appointment->total_price) {
                $appointment->paid += $payment->amount;
            }
            $appointment->save();

            $cashPayment = AppointmentPayment::where('appointment_id', $appointment->id)
                ->whereIn('payment_type', ['cash', 'pos'])
                ->latest()
                ->first();

            $body = [
                "_contract" => [
                    "worker" => $appointment->technician->tech_id ?? null,
                    "SalesOrderId" => $appointment->sales_order_id,
                    "BookId" => $appointment->book_id,
                    "Discount" => $appointment->discount_value ?? 0,
                    "salesLines" => [
                        [
                            'PaymentReference' => $payment->payment_id
                                ?: ($payment->reference_id ?: null),
                            "TotalAmount" => $payment->amount,
                            "PaymentMethod" => "E-Commerce"
                        ],
                        $cashPayment ? [
                            "PaymentReference" => $cashPayment->payment_id
                                ?: ($cashPayment->reference_id ?: null),
                            "TotalAmount" => $cashPayment->total_price,
                            "PaymentMethod"    => strtoupper($cashPayment->payment_type)
                        ] : null
                    ]
                ]
            ];

            $appointment->update([
                'status' => 'processing'
            ]);
            $cmd = "php " . escapeshellarg(base_path('artisan')) . " payments:complete "
                . escapeshellarg($appointment->id) . " "
                . escapeshellarg(json_encode($body))
                . " > /dev/null 2>&1 &";

            exec($cmd);

            try {
                $this->getAppointmentBySalesOrder($appointment->id);
            } catch (\Throwable $e) {
                // Log::error('getAppointmentBySalesOrder failed: ' . $e->getMessage());
            }

            $appointment->save();

            $results[] = [
                'status'          => 'success',
                'priceWithoutTax' => $priceWithoutTax,
                'taxAmount'       => $taxAmount
            ];
            $appointment->update([
                'status' => 'processing'
            ]);
            $appointment->save();
        } else {
            $results[] = [
                'status'          => 'failed',
                'priceWithoutTax' => $priceWithoutTax,
                'taxAmount'       => $taxAmount
            ];
        }

        $overallStatus = collect($results)->every(fn($r) => $r['status'] === 'success') ? 'success' : 'failed';
        $last          = collect($results)->last();
        $priceWithoutTax = $last['priceWithoutTax'] ?? 0;
        $taxAmount       = $last['taxAmount'] ?? 0;

        $lastSalesLineId = $last['sales_line_id'] ?? null;
        $lastPayment = $lastSalesLineId
            ? ClickPayPayment::where('appointment_id', $appointment->id)
            ->where('sales_line_id', $lastSalesLineId)
            ->latest()
            ->first()
            : null;

        return view('Payment.result', [
            'status'        => $overallStatus,
            'appointment'   => $appointment,
            'results'       => $results,
            'payment_type'  => 'clickpay',
            'payment'       => $payment,
            'phone'         => $appointment->phone ?? ($lastPayment->reference_id ?? null),
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount'     => $taxAmount,
        ]);
    }


    public function newHandleReturn(Request $request)
    {
        $sales_order_id = $request->sales_order_id ?? null;

        $directAppointment = DirectAppointment::where('sales_order_id', $sales_order_id)
            ->orderByDesc('id')
            ->first();

        $payment = DirectAppointmentPayment::where('reference_id', $request->reference_id)
            ->where('payment_type', 'E-COMMERCE')
            ->orderBy('id', 'desc')
            ->first();

        if (!$payment || !$directAppointment) {
            return view('Payment.result', [
                'status'          => 'failed',
                'payment_type'    => 'clickpay',
                'message'         => !$payment ? 'Payment not found' : 'Appointment not found',
                'price'           => 0,
                'payment'         => $payment,
                'priceWithoutTax' => 0,
                'taxAmount'       => 0,
            ]);
        }

        $totalAmount     = (float) ($payment->price ?? 0);
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount       = round($totalAmount - $priceWithoutTax, 2);

        $data = $this->clickPay->handleReturn($payment->payment_id);
        $data = json_decode(json_encode($data), true);

        if (
            isset($data['payment_result']['response_message']) &&
            ($data['payment_result']['response_message']) === 'Authorised'
        ) {
            $payment->status = 'paid';

            $directAppointment->collect = max(0, (float) $directAppointment->collect - (float) $payment->price);
            $directAppointment->save();
        } else {
            if ($payment->status !== 'paid') {
                $payment->status = 'failed';
            }
        }

        $payment->save();

        $paidSum = (float) $directAppointment->payments()
            ->where('status', 'paid')
            ->sum('price');

        $discount = (float) ($directAppointment->discount ?? 0);
        $required = (float) ($directAppointment->required_amount ?? 0);

        $allPaid = abs(($paidSum + $discount) - $required) < 0.01;

        if ($allPaid) {
            $appointment = DirectAppointment::where('sales_order_id', $payment->sales_order_id)
                ->orderByDesc('id')
                ->first();

            if ($appointment) {
                $appointment->update([
                    'status'  => 'paid',
                    'collect' => 0,
                ]);
            }
        }

        return view('Payment.result', [
            'status'          => $payment->status,
            'payment_type'    => 'clickpay',
            'payment'         => $payment,
            'phone'           => $payment->phone ?? $payment->reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount'       => $taxAmount,
        ]);
    }

    // dy handle
    /**
     * ClickPay's browser return for a DY payment link.
     *
     * ClickPay is asked FIRST. DY365 hears "Approved" only for a payment ClickPay reports as Authorised
     * (this handler used to say "Approved" before asking anything, so a declined or abandoned payment was
     * reported as approved), and only once. The query string can no longer pick another provider's link.
     */
    public function dyHandleReturn(Request $request)
    {
        $payment = DyPaymentLink::where('payment_method', 'clickpay')
            ->where('dy_reference_id', $request->reference_id)
            ->first();

        if (!$payment) {
            return view('Payment.result', [
                'status'          => 'failed',
                'payment_type'    => 'clickpay',
                'message'         => 'Payment not found',
                "price"           => 0,
                'payment'         => $payment,
                'priceWithoutTax' => 0,
                'taxAmount'       => 0,
            ]);
        }

        // A replay of a payment that is already complete: nothing to verify, nothing to tell DY365 again.
        if (in_array($payment->status, ['paid', 'success'], true)) {
            return $this->dyReturnView($payment, 'paid');
        }

        try {
            $data = json_decode(json_encode($this->clickPay->handleReturn($payment->payment_reference_id)), true);
        } catch (\Throwable $e) {
            Log::error('ClickPay DY return: could not query ClickPay; link left unchanged', [
                'dy_reference_id' => $payment->dy_reference_id,
                'error'           => $e->getMessage(),
            ]);

            return $this->dyReturnView($payment, 'failed');
        }

        $message = $data['payment_result']['response_message'] ?? null;

        if ($message === null) {
            // no usable answer from ClickPay: change nothing, tell nobody
            Log::warning('ClickPay DY return: unusable reply; link left unchanged', ['dy_reference_id' => $payment->dy_reference_id]);

            return $this->dyReturnView($payment, 'failed');
        }

        if ($message !== 'Authorised') {
            // ClickPay explicitly did not authorise it (declined, voided, expired...). DY365 is no longer told "Approved".
            if (in_array($payment->status, ['created', 'pending', 'cancelled'], true)) {
                $payment->update(['status' => 'failed']);
            }

            return $this->dyReturnView($payment->fresh(), 'failed');
        }

        // Authorised: it must also be the amount and currency this link was created for.
        $charged  = $data['cart_amount'] ?? null;
        $currency = $data['cart_currency'] ?? null;

        if (($charged !== null && (!is_numeric($charged) || abs((float) $charged - (float) $payment->amount) >= 0.005))
            || ($currency !== null && strtoupper((string) $currency) !== 'SAR')
        ) {
            Log::warning('ClickPay DY return: authorised payment does not match the link amount/currency; link left unchanged', [
                'dy_reference_id' => $payment->dy_reference_id,
            ]);

            return $this->dyReturnView($payment, 'failed');
        }

        try {
            DB::transaction(function () use ($payment) {
                $locked = DyPaymentLink::whereKey($payment->id)->lockForUpdate()->first();

                if (in_array($locked->status, ['paid', 'success'], true)) {
                    return;   // a concurrent or repeated request got here first
                }

                $reply = app(DyController::class)->handlePaymentStatus($locked, 'Approved');

                if (!DyAcknowledgement::accepted($reply)) {
                    // A non-null $reply here is DY365's own 200-OK business rejection (Status:false / Error set),
                    // not a transport failure (those are swallowed and logged separately, to
                    // storage/logs/dyservice/dyPaymentStatus.log) — until now, logged nowhere at all.
                    Log::error('DY365 refused a payment-link status notification', [
                        'dy_reference_id'      => $locked->dy_reference_id,
                        'payment_reference_id' => $locked->payment_reference_id,
                        'requested_status'     => 'Approved',
                        'current_local_status' => $locked->status,
                        'dy_reply'             => $reply,
                    ]);

                    throw new \RuntimeException('DY365 did not accept the Approved notification');
                }

                $locked->update(['status' => 'paid']);
            });
        } catch (\Throwable $e) {
            Log::error('ClickPay DY return: DY365 did not accept the notification; link left unchanged', [
                'dy_reference_id' => $payment->dy_reference_id,
                'error'           => $e->getMessage(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'The payment was verified but DY365 could not be notified. Please refresh to try again.'], 502);
        }

        return $this->dyReturnView($payment->fresh(), 'paid');
    }

    private function dyReturnView(DyPaymentLink $payment, string $status)
    {
        // The link's amount column is `amount` (this used to read a `price` column that does not exist, so the page showed 0).
        $totalAmount     = (float) $payment->amount;
        $priceWithoutTax = round($totalAmount / 1.15, 2);
        $taxAmount       = round($totalAmount - $priceWithoutTax, 2);

        return view('Payment.result', [
            'status'          => $status,
            'payment_type'    => 'clickpay',
            'payment'         => $payment,
            'phone'           => $payment->phone ?? $payment->payment_reference_id,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount'       => $taxAmount,
        ]);
    }

    /**
     * ClickPay callback (webhook) handler — legacy Appointment flow.
     *
     * FIXED: this used to be `return "handleCallback";` as its very
     * first line — a complete no-op that never processed anything.
     * Now actually queries ClickPay and applies the verified status.
     */
    public function handleCallback(Request $request)
    {
        Log::info('ClickPay legacy callback received.', ['payload' => $request->all()]);

        $tranRef = $request->input('tran_ref');

        if (!$tranRef) {
            return response()->json(['status' => false, 'message' => 'Missing tran_ref'], 400);
        }

        $payment = ClickPayPayment::where('reference_id', $tranRef)->first();

        if (!$payment) {
            return response()->json(['status' => true, 'message' => 'Ignored — payment not found']);
        }

        try {
            $data = $this->clickPay->queryPayment($tranRef);
        } catch (\Throwable $e) {
            Log::error('ClickPay legacy callback query failed.', ['tran_ref' => $tranRef, 'error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Query failed'], 500);
        }

        $status = $data['payment_result']['response_message'] ?? null;

        if ($status === 'Authorised' && $payment->status !== 'success') {
            $payment->update(['status' => 'success']);
        } elseif (in_array($status, ['Declined', 'Voided', 'Expired'], true) && $payment->status !== 'success') {
            $payment->update(['status' => 'failed']);
        }

        return response()->json(['message' => 'Callback processed', 'data' => $data]);
    }

    /**
     * ClickPay callback (webhook) handler — "new" DirectAppointment flow.
     *
     * NEW: this route already existed in routes/api.php pointing at
     * this method name, but the method itself never existed — any
     * request here threw a fatal error. This means the active payment
     * flow (createInvoiceNew) had NO working async server-to-server
     * confirmation at all, relying entirely on the customer's browser
     * completing the /return redirect (which fails silently if they
     * close the tab, lose connection, etc). This method fixes that gap.
     */
    public function newHandleCallback(Request $request, ClickPayPaymentSyncService $syncService)
    {
        $tranRef = $request->input('tran_ref');
        $referenceId = $request->input('reference_id') ?? $request->input('cart_id');

        Log::info('ClickPay callback received.', ['payload' => $request->all()]);

        if (!$tranRef && !$referenceId) {
            return response()->json(['status' => false, 'message' => 'Missing tran_ref/reference_id'], 400);
        }

        $payment = null;

        if ($tranRef) {
            $payment = DirectAppointmentPayment::where('payment_id', $tranRef)
                ->orWhere('reference_id', $tranRef)
                ->first();
        }

        if (!$payment && $referenceId) {
            $payment = DirectAppointmentPayment::where('reference_id', $referenceId)->first();
        }

        if (!$payment) {
            Log::warning('ClickPay callback: local payment not found.', [
                'tran_ref'     => $tranRef,
                'reference_id' => $referenceId,
            ]);

            return response()->json(['status' => true, 'message' => 'Ignored — payment not found']);
        }

        if (!$payment->payment_id && $tranRef) {
            $payment->update(['payment_id' => $tranRef]);
        }

        try {
            $result = $syncService->syncPayment($payment);
        } catch (\Throwable $e) {
            Log::error('ClickPay callback sync failed.', [
                'payment_id' => $payment->payment_id,
                'error'      => $e->getMessage(),
            ]);

            return response()->json(['status' => false, 'message' => 'Sync failed'], 500);
        }

        return response()->json(['status' => true, 'message' => 'Callback processed', 'data' => $result]);
    }

    /**
     * NEW: same situation as newHandleCallback — referenced in
     * routes/api.php but the method didn't exist. Mirrors refund()
     * but scoped to the DirectAppointmentPayment ("new") flow.
     */
    public function newRefund(Request $request)
    {
        // INTERNAL endpoint, not a ClickPay callback: it moves real money, so routes/api.php
        // puts it behind auth:sanctum + an admin role. Everything below is defence in depth.
        $validated = $request->validate([
            'reference_id' => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'reason'       => 'nullable|string|max:255',
        ]);

        $actorId = $request->user()?->id;

        // One refund at a time per payment: the row is locked while the provider is called, so a
        // double click or a retry cannot refund twice.
        return DB::transaction(function () use ($validated, $actorId) {
            $payment = DirectAppointmentPayment::where('reference_id', $validated['reference_id'])
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
            }

            if ($payment->status !== 'paid') {
                return response()->json(['success' => false, 'message' => 'Only paid payments can be refunded.'], 422);
            }

            $amount = round((float) $validated['amount'], 2);

            if ($amount > round((float) $payment->price, 2)) {
                return response()->json(['success' => false, 'message' => 'Refund amount exceeds the paid amount.'], 422);
            }

            $tranRef = $payment->payment_id ?: $payment->reference_id;
            $reason  = $validated['reason'] ?? 'Customer refund';

            Log::info('ClickPay refund requested', [
                'by_user_id'   => $actorId,
                'reference_id' => $payment->reference_id,
                'amount'       => $amount,
            ]);

            try {
                $response = $this->clickPay->refund($tranRef, $amount, $reason);
            } catch (\Throwable $e) {
                // The outcome at ClickPay is unknown (timeout, non-JSON reply...). Never guess "refunded":
                // leave the payment untouched and tell the admin to confirm it in the ClickPay dashboard.
                Log::error('ClickPay refund could not be confirmed; payment left unchanged', [
                    'by_user_id'   => $actorId,
                    'reference_id' => $payment->reference_id,
                    'error'        => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'The refund could not be confirmed with ClickPay. Check the ClickPay dashboard before retrying.',
                ], 502);
            }

            if (isset($response['response_code']) && $response['response_code'] === '000') {
                $payment->update(['status' => 'refunded']);

                Log::info('ClickPay refund succeeded', [
                    'by_user_id'   => $actorId,
                    'reference_id' => $payment->reference_id,
                    'amount'       => $amount,
                ]);

                return response()->json([
                    'success'  => true,
                    'message'  => 'Refund successful.',
                    'response' => $response,
                ]);
            }

            Log::warning('ClickPay refund failed', [
                'by_user_id'   => $actorId,
                'reference_id' => $payment->reference_id,
                'response'     => $response,
            ]);

            return response()->json([
                'success'  => false,
                'message'  => 'Refund failed.',
                'response' => $response,
            ], 422);
        });
    }

    // NEW: GET /integration/clickpay/payment-details?tran_ref=...
    // GET /integration/clickpay/payment-details?reference_id=...
    public function getPaymentDetails(Request $request)
    {
        $request->validate([
            'tran_ref'     => 'nullable|string|required_without:reference_id',
            'reference_id' => 'nullable|string|required_without:tran_ref',
        ]);

        $tranRef = $request->input('tran_ref');
        $localPayment = null;

        if (!$tranRef) {
            $localPayment = DirectAppointmentPayment::where('reference_id', $request->input('reference_id'))->first();

            if (!$localPayment) {
                return response()->json([
                    'status'  => false,
                    'message' => "No local payment found for reference_id '{$request->input('reference_id')}'.",
                ], 404);
            }

            $tranRef = $localPayment->payment_id ?: $localPayment->reference_id;
        } else {
            $localPayment = DirectAppointmentPayment::where('payment_id', $tranRef)
                ->orWhere('reference_id', $tranRef)
                ->first();
        }

        try {
            $clickpayData = $this->clickPay->queryPayment($tranRef);
        } catch (\Throwable $e) {
            Log::error('Failed to query ClickPay payment.', ['tran_ref' => $tranRef, 'error' => $e->getMessage()]);

            return response()->json([
                'status'  => false,
                'message' => 'Failed to retrieve payment from ClickPay.',
                'error'   => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'clickpay_payment' => $clickpayData,
                'local_payment'    => $localPayment,
            ],
        ]);
    }

    // NEW: GET /integration/clickpay/sync-payment-status?tran_ref=...
    // GET /integration/clickpay/sync-payment-status?reference_id=...
    public function syncPaymentStatus(Request $request, ClickPayPaymentSyncService $syncService)
    {
        $request->validate([
            'tran_ref'     => 'nullable|string|required_without:reference_id',
            'reference_id' => 'nullable|string|required_without:tran_ref',
        ]);

        if ($request->filled('tran_ref')) {
            $payment = DirectAppointmentPayment::where('payment_id', $request->input('tran_ref'))
                ->orWhere('reference_id', $request->input('tran_ref'))
                ->first();
        } else {
            $payment = DirectAppointmentPayment::where('reference_id', $request->input('reference_id'))->first();
        }

        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'No local payment found for the given identifier.'], 404);
        }

        try {
            $result = $syncService->syncPayment($payment);
        } catch (\Throwable $e) {
            Log::error('Manual ClickPay payment sync failed.', [
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
                'tran_ref'     => $payment->payment_id,
                ...$result,
            ],
        ]);
    }
}
