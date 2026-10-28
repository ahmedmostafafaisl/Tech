<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Requests\Payment\ConfirmNearPayPaymentRequest;
use App\Http\Requests\Payment\CreateNearPayPaymentRequest;
use App\Http\Requests\Payment\HandleNfcPaymentResponseRequest;
use App\Models\DirectAppointment;
use App\Models\DirectAppointmentPayment;
use App\Services\CheckCompleteStatus\CheckCompleteService;
use App\Services\Payment\NearPayPaymentSyncService;
use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * NearPay is a phone-based SDK, not a checkout link: the technician's own device talks to NearPay directly
 * to take the card payment, so there is no "send a payment link" step here the way there is for
 * Tabby/Tamara/ClickPay. Instead there are two calls from the app around that device-side transaction:
 *
 *   1. create()  — before the card is tapped: register a local, pending payment for a known amount.
 *   2. confirm() — after NearPay reports success on-device: hand back that payment's own reference plus
 *                  the NearPay transaction id, so it can be claimed and verified server-side.
 *
 * Neither endpoint ever marks a payment paid by itself — confirm() only *links* the transaction id to the
 * local payment and then delegates the actual verdict to NearPayPaymentSyncService, the same service the
 * webhook job uses.
 */
class NearPayPaymentController extends \App\Http\Controllers\Controller
{
    public function __construct(
        private readonly CheckCompleteService $checkCompleteService,
        private readonly NearPayPaymentSyncService $syncService,
    ) {}

    public function create(CreateNearPayPaymentRequest $request)
    {
        $validated = $request->validated();

        $appointment = DirectAppointment::where('book_id', $validated['book_id'])
            ->where('sales_order_id', $validated['sales_order_id'])
            ->latest('id')
            ->first();

        if (!$appointment) {
            return response()->json(['status' => false, 'message' => 'Appointment not found.'], 404);
        }

        $this->checkCompleteService->authorizeTechOrFail(
            $appointment->tech_id,
            "Unauthorized NearPay payment creation attempt for book_id {$validated['book_id']}"
        );

        // The amount is whatever the technician entered on-device, but it can never exceed what is
        // actually still owed on this appointment — the server, not the request body, is the source of
        // truth for the ceiling. required_amount / collect already reflect this app's own existing rule
        // for "what's left to pay" elsewhere; nothing new is invented here.
        $outstanding = max(
            (float) ($appointment->collect ?? 0),
            (float) ($appointment->required_amount ?? 0)
        );

        if ($outstanding <= 0 || (float) $validated['amount'] > $outstanding + 0.01) {
            Log::warning('NearPay payment creation amount exceeds the appointment\'s outstanding balance.', [
                'book_id'     => $validated['book_id'],
                'requested'   => $validated['amount'],
                'outstanding' => $outstanding,
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'The requested amount exceeds what is still owed on this appointment.',
            ], 422);
        }

        $payment = DirectAppointmentPayment::create([
            'direct_appointment_id' => $appointment->id,
            'sales_order_id'        => $appointment->sales_order_id,
            'book_id'               => $appointment->book_id,
            'price'                 => $validated['amount'],
            'status'                => 'pending',
            'payment_type'          => 'NEARPAY',
            'phone'                 => $validated['phone'] ?? null,
            'reference_id'          => 'NEARPAY-' . Str::uuid(),
        ]);

        return response()->json([
            'status' => true,
            'data'   => [
                'reference_id' => $payment->reference_id,
                'amount'       => $payment->price,
            ],
        ], 201);
    }

    public function confirm(ConfirmNearPayPaymentRequest $request)
    {
        $validated = $request->validated();

        $payment = DirectAppointmentPayment::where('reference_id', $validated['reference_id'])
            ->where('payment_type', 'NEARPAY')
            ->first();

        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Payment not found.'], 404);
        }

        $appointment = $payment->directAppointment;

        // The reference_id alone is a long, unguessable UUID, but ownership is still re-checked here: the
        // only thing that should ever be able to claim this payment is the SAME technician it was created
        // for, not anyone who merely obtained the reference string.
        $this->checkCompleteService->authorizeTechOrFail(
            $appointment?->tech_id,
            "Unauthorized NearPay confirm attempt for reference_id {$validated['reference_id']}"
        );

        $claim = $this->claimTransaction($payment, $validated['transaction_id']);

        if (!$claim['ok']) {
            return response()->json(['status' => false, 'message' => $claim['message']], 409);
        }

        try {
            $result = $this->syncService->syncPayment($payment->fresh());
        } catch (\Throwable $e) {
            Log::error('NearPay confirm: verification failed.', [
                'reference_id'   => $payment->reference_id,
                'transaction_id' => $validated['transaction_id'],
                'error'          => $e->getMessage(),
            ]);

            return response()->json([
                'status'  => false,
                'message' => 'Could not verify this payment with NearPay right now. It will be re-checked automatically.',
            ], 503);
        }

        return response()->json(['status' => true, 'data' => $result]);
    }

    /**
     * Links a NearPay transaction id to this local payment — the ONE step that establishes the mapping a
     * webhook alone cannot safely make on its own (NearPay's webhook payload carries no reference back to
     * our own order/payment). Refuses to let a transaction id already claimed by a DIFFERENT payment be
     * reused, and refuses to silently overwrite a payment already claimed with a DIFFERENT transaction id —
     * both checked and written inside one locked transaction, closing the race between two concurrent
     * confirm()/handleNfcResponse() calls for the same payment or the same transaction id.
     *
     * $paymentType scopes the "already claimed by a different payment" check — both confirm() and
     * handleNfcResponse() pass 'NEARPAY' (the one shared type for this payment method, however it was
     * created), so the parameter stays generic rather than hardcoding that string inside this method itself.
     *
     * $extraAttributes are written in the SAME locked update as payment_id, not a separate call afterwards —
     * e.g. handleNfcResponse()'s retrieval_reference_number, which has no meaning without a successful claim.
     *
     * @return array{ok: bool, message?: string}
     */
    private function claimTransaction(DirectAppointmentPayment $payment, string $transactionId, string $paymentType = 'NEARPAY', array $extraAttributes = []): array
    {
        return DB::transaction(function () use ($payment, $transactionId, $paymentType, $extraAttributes) {
            $locked = DirectAppointmentPayment::where('id', $payment->id)->lockForUpdate()->first();

            if (!$locked) {
                return ['ok' => false, 'message' => 'Payment not found.'];
            }

            if ($locked->payment_id !== null && $locked->payment_id !== $transactionId) {
                Log::warning('NearPay confirm: payment already claimed by a different transaction id.', [
                    'reference_id'        => $locked->reference_id,
                    'existing_payment_id' => $locked->payment_id,
                    'attempted'           => $transactionId,
                ]);

                return ['ok' => false, 'message' => 'This payment is already linked to a different transaction.'];
            }

            $reusedElsewhere = DirectAppointmentPayment::where('payment_type', $paymentType)
                ->where('payment_id', $transactionId)
                ->where('id', '!=', $locked->id)
                ->lockForUpdate()
                ->exists();

            if ($reusedElsewhere) {
                Log::warning('NearPay confirm: transaction id already claimed by a different payment.', [
                    'reference_id'   => $locked->reference_id,
                    'transaction_id' => $transactionId,
                ]);

                return ['ok' => false, 'message' => 'This transaction is already linked to a different payment.'];
            }

            if ($locked->payment_id === null) {
                $locked->update(array_merge(['payment_id' => $transactionId], $extraAttributes));
            } elseif (!empty($extraAttributes)) {
                $locked->update($extraAttributes);
            }

            return ['ok' => true];
        });
    }

    /**
     * NFC (tap-to-pay) counterpart to confirm() — same underlying NearPay mechanism and the SAME
     * payment_type ('NEARPAY') as confirm()/create(), just a different entry point (sendPaymentLinks, not
     * create()) with a different response shape to match. customer_reference_number is this app's own
     * reference_id; payment_id is NearPay's transaction id, verified server-side exactly like confirm()
     * does — a client-reported outcome is never trusted on its own.
     */
    public function handleNfcResponse(HandleNfcPaymentResponseRequest $request)
    {
        $validated = $request->validated();

        $payment = DirectAppointmentPayment::where('reference_id', $validated['customer_reference_number'])
            ->where('payment_type', 'NEARPAY')
            ->first();

        if (!$payment) {
            return response()->json(['status' => false, 'message' => 'Payment not found.'], 404);
        }

        $appointment = $payment->directAppointment;

        $this->checkCompleteService->authorizeTechOrFail(
            $appointment?->tech_id,
            "Unauthorized NFC payment response for customer_reference_number {$validated['customer_reference_number']}"
        );

        $claim = $this->claimTransaction(
            $payment,
            $validated['payment_id'],
            'NEARPAY',
            ['retrieval_reference_number' => $validated['retrieval_reference_number']]
        );

        if (!$claim['ok']) {
            return response()->json(['status' => false, 'message' => $claim['message']], 409);
        }

        try {
            $result = $this->syncService->syncPayment($payment->fresh());
            TelegramService::send('NFC payment response:result ' . json_encode($result) . ' for reference_id ' . $payment->reference_id);
        } catch (\Throwable $e) {

            TelegramService::send('NFC payment response: verification failed for reference_id ' . $payment->reference_id . '. Error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Could not verify this payment with NearPay right now. It will be re-checked automatically.',
            ], 503);
        }

        return response()->json(['status' => true, 'data' => $result]);
    }
}
