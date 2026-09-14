<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\DirectAppointment;
use App\Services\Payment\TamaraService;
use App\Services\Payment\PaymentCompletionDispatcher;

class RetryPaymentCompletion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $backoff = 120; // seconds between retries

    public function __construct(public readonly int $appointmentId) {}

    public function handle(): void
    {
        $appointment = DirectAppointment::find($this->appointmentId);

        if (!$appointment || $appointment->status === 'completed') {
            return; // nothing to do
        }

        // Re-resolve orderId from the most recent paid payment
        $payment = $appointment->payments()->where('status', 'paid')->latest()->first();
        $orderId = $payment?->payment_id;

        if (!$orderId) {
            Log::warning('RetryPaymentCompletion: still no orderId', [
                'appointment_id' => $this->appointmentId,
            ]);
            $this->release(120); // put back on the queue
            return;
        }

        $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);
        $tamaraStatus   = $statusResponse['status'] ?? null;
        $isCaptured     = in_array($tamaraStatus, ['captured', 'fully_captured'], true);

        if (!$isCaptured) {
            Log::info('RetryPaymentCompletion: still not captured, will retry', [
                'appointment_id' => $this->appointmentId,
                'tamara_status'  => $tamaraStatus,
            ]);
            $this->release(120);
            return;
        }

        $result = app(PaymentCompletionDispatcher::class)->dispatch($appointment);

        if (!($result['ok'] ?? false)) {
            Log::warning('RetryPaymentCompletion: dispatch returned not-ok', [
                'appointment_id' => $this->appointmentId,
                'result'         => $result,
            ]);
        }
    }
}
