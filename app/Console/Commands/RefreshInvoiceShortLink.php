<?php

namespace App\Console\Commands;

use App\Models\DirectAppointment;
use App\Models\ShortLink;
use App\Services\TaqnyatSmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Regenerates the ShortLink for a given book_id's invoice (deletes any
 * existing record(s), creates a fresh one — new code, sent reset to
 * false) and resends it via SMS, using the exact same
 * TaqnyatSmsService::sendPdfLink() call InvoiceController::sendInvoiceLink()
 * already uses — one source of truth for how this SMS gets sent.
 *
 * The underlying PDF URL (/api/invoice/{bookId}/download) is a dynamic
 * route, so invoice content itself is already regenerated fresh on every
 * visit — this command exists for replacing the short link record and
 * getting a fresh message out to the customer.
 *
 * php artisan invoice:refresh-short-link APP001430781
 * php artisan invoice:refresh-short-link APP001430781 --phone=966501234567
 */
class RefreshInvoiceShortLink extends Command
{
    protected $signature = 'invoice:refresh-short-link
                            {book_id}
                            {--phone= : Override the phone to send to instead of the appointment\'s stored customer_phone}';

    protected $description = 'Delete and recreate the ShortLink for a given book_id\'s invoice, then resend it via SMS';

    public function handle(TaqnyatSmsService $smsService): int
    {
        $bookId = $this->argument('book_id');

        // ── 1) Resolve phone ────────────────────────────────────────────
        $phone = $this->option('phone');

        if (!$phone) {
            $directAppointment = DirectAppointment::where('book_id', $bookId)
                ->latest('id')
                ->first();

            $phone = $directAppointment->customer_phone ?? null;
        }

        if (!$phone) {
            $this->error("No phone number available for book_id {$bookId} — pass one explicitly with --phone=.");
            return self::FAILURE;
        }

        // ── 2) Delete existing short link(s), create a fresh one ────────
        $existing = ShortLink::where('book_id', $bookId)->get();

        if ($existing->isNotEmpty()) {
            $this->info("Deleting {$existing->count()} existing ShortLink record(s) for book_id {$bookId}:");
            foreach ($existing as $link) {
                $this->line("  - code: {$link->code}, sent: " . ($link->sent ? 'yes' : 'no') . ", hits: {$link->hits}");
            }
            ShortLink::where('book_id', $bookId)->delete();
        } else {
            $this->info("No existing ShortLink found for book_id {$bookId} — creating a new one.");
        }

        $code = $this->generateUniqueCode();
        $pdfUrl = url('/api/invoice/' . $bookId . '/download');

        $shortLink = ShortLink::create([
            'book_id' => $bookId,
            'code'    => $code,
            'url'     => $pdfUrl,
            'sent'    => false,
        ]);

        $shortUrl = url('/i/' . $shortLink->code);

        $this->info('New short link created:');
        $this->line("  code:      {$shortLink->code}");
        $this->line("  short_url: {$shortUrl}");
        $this->line("  file_url:  {$pdfUrl}");

        // ── 3) Resend via SMS — same call sendInvoiceLink() uses ────────
        $this->info("Sending to {$phone}...");

        $smsResponse = $smsService->sendPdfLink($phone, $shortUrl);

        $smsSent = is_array($smsResponse)
            && isset($smsResponse['statusCode'])
            && (int) $smsResponse['statusCode'] === 201;

        if ($smsSent) {
            $shortLink->update(['sent' => true]);
            $this->info('SMS sent successfully.');
        } else {
            Log::warning('SMS إرسال فشل (invoice:refresh-short-link)', [
                'book_id'  => $bookId,
                'phone'    => $phone,
                'response' => $smsResponse,
            ]);
            $this->error('SMS send failed — see logs for details.');
            $this->line(json_encode($smsResponse));
        }

        return $smsSent ? self::SUCCESS : self::FAILURE;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::random(8);
        } while (ShortLink::where('code', $code)->exists());

        return $code;
    }
}
