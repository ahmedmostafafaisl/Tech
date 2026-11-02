<?php

namespace App\Jobs;

use App\Models\InvoiceWhatsAppMessage;
use App\Services\DY365\DyService;
use App\Services\InvoiceWhatsApp\InvoiceWhatsAppSenderService;
use App\Services\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * The background half of the new, independent invoice-via-WhatsApp cycle — entirely separate from
 * SendInvoiceLinkJob, which this does not call, extend, or share any state with (no shared ShortLink row,
 * no shared settings beyond the Meta template name itself). One new InvoiceWhatsAppMessage row is created
 * per attempt, up front, the same "a record exists even if nothing ends up sent" principle the existing
 * cycle already follows — so a book_id with no invoice yet still leaves a durable trace this was tried.
 *
 * DY365 is still checked before sending (via the same shared DyService every invoice flow uses) — there is
 * no point handing Meta a Document header pointing at a PDF that doesn't exist yet.
 */
class SendInvoiceWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $bookId,
        private readonly string $phone,
    ) {}

    public function handle(DyService $dyService, InvoiceWhatsAppSenderService $sender): void
    {
        $message = InvoiceWhatsAppMessage::create([
            'book_id' => $this->bookId,
            'phone'   => $this->phone,
            'status'  => 'pending',
        ]);

        try {
            $response = $dyService->getOrCreateInvoiceByBookId(['bookId' => $this->bookId]);
        } catch (\Throwable $e) {
            Log::warning('SendInvoiceWhatsAppMessageJob: DY365 call failed, nothing sent', [
                'book_id' => $this->bookId,
                'phone'   => $this->phone,
                'error'   => $e->getMessage(),
            ]);

            $message->update(['status' => 'failed', 'error' => 'DY365 call failed: ' . $e->getMessage()]);

            return;
        }

        if (
            ! is_array($response)
            || empty($response['Status'])
            || empty($response['Data'])
            || ! is_array($response['Data'])
        ) {
            Log::warning('SendInvoiceWhatsAppMessageJob: no invoice data from DY365, nothing sent', [
                'book_id'  => $this->bookId,
                'phone'    => $this->phone,
                'response' => $response,
            ]);

            $message->update(['status' => 'failed', 'error' => 'Invoice not generated yet']);

            return;
        }

        $result = $sender->send($this->bookId, $this->phone);

        if (! empty($result['success'])) {
            $message->update([
                'status'     => 'sent',
                'message_id' => $result['message_id'],
                'response'   => json_encode($result['response'] ?? null),
            ]);

            return;
        }

        Log::warning('SendInvoiceWhatsAppMessageJob: send failed', [
            'book_id' => $this->bookId,
            'phone'   => $this->phone,
            'error'   => $result['error'] ?? null,
        ]);

        $message->update([
            'status'   => 'failed',
            'error'    => $result['error'] ?? 'Unknown error',
            'response' => json_encode($result['response'] ?? null),
        ]);

        TelegramService::send(
            "⚠️ New-cycle invoice WhatsApp send failed\n\n"
                . "book_id: {$this->bookId}\n"
                . "phone: {$this->phone}\n"
                . "error: " . ($result['error'] ?? 'Unknown error')
        );
    }
}
