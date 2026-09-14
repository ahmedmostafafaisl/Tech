<?php

namespace App\Console\Commands;

use App\Models\PreAppointmentMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\DY365\DyService;

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

            $requestBody = [
                '_contract' => [
                    'bookId'       => $msg->book_id ?? null,
                    'salesOrderId' => $msg->sales_order ?? null,
                    'actionOwner'  => 2,
                ],
            ];

            // map response to requestType
            match ($customerResponse) {
                'confirm'    => $requestBody['_contract']['requestType'] = 2,
                'cancel'     => $requestBody['_contract']['requestType'] = 0,
                'reschedule' => $requestBody['_contract']['requestType'] = 1,
                default      => $requestBody['_contract']['requestType'] = null,
            };

            if ($requestBody['_contract']['requestType'] === null) {
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
                // ✅ use sendRequest3 style? adapt to your dyService method:
                $dyResponse = $this->dyService->sendRequest3(
                    'post',
                    $this->dyService->customerChangeRequest,
                    $requestBody
                );

                // log dy response always
                Log::channel('whatsapp')->info('📥 DY response', [
                    'sales_order' => $salesOrder,
                    'dy_response' => $dyResponse,
                ]);

                // store dy_response + flag on message (optional but recommended)
                $ok = ($dyResponse['ok'] ?? false) === true
                    && (data_get($dyResponse, 'data.Status') === true);

                $msg->update([
                    'flag'        => $ok ? 1 : 0,
                    'dy_response' => json_encode($dyResponse, JSON_UNESCAPED_UNICODE),
                ]);

                $ok
                    ? $this->info("✅ Sent: {$salesOrder}")
                    : $this->error("❌ Failed: {$salesOrder}");
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
