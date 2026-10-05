<?php

namespace App\Console\Commands;

use App\Models\PreAppointmentMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\DY365\DyService;
use App\Services\WhatsApp\CustomerResponseRequest;

class SendCustomerResponsesToDyCommand extends Command
{
    protected $signature = 'dy:send-customer-responses {sales_orders*}';
    protected $description = 'Send customer responses to DY365 for given sales orders (latest message only).';

    public function __construct(protected DyService $dyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $salesOrders = $this->argument('sales_orders');

        foreach ($salesOrders as $salesOrder) {

            $msg = PreAppointmentMessage::where('sales_order', $salesOrder)
                ->latest('id')
                ->first();

            if (!$msg) {
                $this->warn("⚠️ Not found: {$salesOrder}");
                Log::channel('whatsapp')->warning('PreAppointmentMessage not found', [
                    'sales_order' => $salesOrder,
                ]);
                continue;
            }

            $customerResponse = $msg->customer_response; // pending|confirm|reschedule|cancel

            // ✅ IMPORTANT: pending => do not send (no decision yet)
            if ($customerResponse === 'pending') {
                $this->warn("⏭️ Skipped (pending): {$salesOrder}");
                Log::channel('whatsapp')->info('Skipped sending to DY (pending)', [
                    'sales_order' => $salesOrder,
                    'pre_appointment_message_id' => $msg->id,
                ]);
                continue;
            }

            try {
                $requestBody = CustomerResponseRequest::body($msg->book_id, $msg->sales_order, (string) $customerResponse);
            } catch (\InvalidArgumentException $e) {
                $this->error("❌ Unknown customer_response: {$customerResponse} for {$salesOrder}");
                Log::channel('whatsapp')->warning('Unknown customer_response', [
                    'sales_order' => $salesOrder,
                    'customer_response' => $customerResponse,
                ]);
                continue;
            }

            Log::channel('whatsapp')->info('📤 Sending customer response to DY', [
                'sales_order' => $salesOrder,
                'customer_response' => $customerResponse,
                'request_body' => $requestBody,
            ]);

            try {
                // One time-limited attempt. A change request is not idempotent, and this used to
                // wait up to 500s and resend it up to 3 times (sendRequest3 with retries).
                $outcome = $this->dyService->submitCustomerChangeRequestWithin($requestBody, 90);
                $record  = CustomerResponseRequest::record($outcome);

                // log dy response always
                Log::channel('whatsapp')->info('📥 DY response', [
                    'sales_order' => $salesOrder,
                    'dy_response' => $record['dy_response'],
                ]);

                $ok = $record['flag'] === 1;

                $msg->update([
                    'flag'        => $record['flag'],
                    'dy_response' => json_encode($record['dy_response'], JSON_UNESCAPED_UNICODE),
                ]);

                $ok
                    ? $this->info("✅ Sent: {$salesOrder}")
                    : $this->error("❌ Failed: {$salesOrder} ({$record['dy_response']['outcome']})");
            } catch (\Throwable $e) {
                Log::channel('whatsapp')->error('❌ DY send customer response exception', [
                    'sales_order' => $salesOrder,
                    'error' => $e->getMessage(),
                ]);

                $msg->update([
                    'flag'        => 0,
                    'dy_response' => json_encode(['exception' => $e->getMessage()], JSON_UNESCAPED_UNICODE),
                ]);

                $this->error("❌ Exception: {$salesOrder} => {$e->getMessage()}");
            }
        }

        $this->info('🎯 Done sending customer responses.');
        return Command::SUCCESS;
    }
}
