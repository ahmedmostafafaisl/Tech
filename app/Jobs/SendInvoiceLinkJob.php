<?php

namespace App\Jobs;

use App\Models\DirectAppointment;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Services\DY365\DyService;
use App\Services\Evaluation\EvaluationLinkResolver;
use App\Services\TaqnyatSmsService;
use App\Services\WhatsApp\WhatsAppConfirmationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The actual work behind POST /api/invoice/send-link, moved off the request/response cycle: checking
 * DY365 for an invoice (getOrCreateInvoiceByBookId) and generating a PDF are both slow enough that doing
 * them inline made the endpoint itself slow, and — per the review this follows from — the endpoint
 * previously sent a link to the customer with NO verification an invoice existed at all. This job performs
 * that check first; only a confirmed, valid DY365 response results in a ShortLink being created and a
 * message being sent.
 *
 * Which channel(s) actually send is controlled by two independent settings — both can be on at once:
 *   - invoice_send_sms_active (default: ON — this preserves the endpoint's existing behavior)
 *   - invoice_send_whatsapp_active (default: OFF — no approved WhatsApp template exists yet; turning this
 *     on before invoice_whatsapp_template_name holds a real, Meta-approved template name will just log a
 *     failed send, not crash anything)
 * The WhatsApp template name itself is NOT hardcoded — it's read from the invoice_whatsapp_template_name
 * setting, so a newly-approved template can be wired in by changing a setting value, not by a code change.
 *
 * Sent alongside the invoice link in both channels, when resolvable: the appointment's evaluation/rating
 * link (EvaluationLinkResolver, keyed off order_type). This is IN ADDITION TO the separate evaluation
 * WhatsApp message EvaluationMessageController::createAndSendEvaluation() already sends elsewhere — that
 * one is unchanged and keeps sending on its own. If order_type can't be resolved to a known evaluation
 * link, or the appointment record can't be found at all, the invoice link is still sent on its own rather
 * than failing the whole job over a missing, secondary piece of information.
 */
class SendInvoiceLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $bookId,
        private readonly string $phone,
        private readonly bool $forceResend = false,
    ) {}

    public function handle(DyService $dyService, TaqnyatSmsService $smsService, WhatsAppConfirmationService $whatsAppService): void
    {
        try {
            $response = $dyService->getOrCreateInvoiceByBookId(['bookId' => $this->bookId]);
        } catch (\Throwable $e) {
            Log::warning('SendInvoiceLinkJob: DY365 call failed, no link sent', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        if (
            ! is_array($response) ||
            empty($response['Status']) ||
            empty($response['Data']) ||
            ! is_array($response['Data'])
        ) {
            Log::warning('SendInvoiceLinkJob: no invoice data from DY365, no link sent', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'response' => $response,
            ]);
            return;
        }

        $pdfUrl = url('/api/invoice/' . $this->bookId . '/download');

        $shortLink = ShortLink::where('book_id', $this->bookId)->latest('id')->first();

        if (! $shortLink) {
            $shortLink = ShortLink::create([
                'book_id' => $this->bookId,
                'code'    => $this->generateUniqueCode(),
                'url'     => $pdfUrl,
                'sent'    => false,
            ]);
        }

        if ($shortLink->sent && ! $this->forceResend) {
            Log::info('SendInvoiceLinkJob: link already sent previously, skipping', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
            ]);
            return;
        }

        $shortUrl = url('/' . $shortLink->code);

        $orderType = DirectAppointment::where('book_id', $this->bookId)->latest('id')->value('order_type');
        $evaluationLink = EvaluationLinkResolver::resolve($orderType);

        $sentViaAny = false;

        if (Setting::isActive('invoice_send_sms_active', true)) {
            $sentViaAny = $this->sendViaSms($shortUrl, $evaluationLink) || $sentViaAny;
        }

        if (Setting::isActive('invoice_send_whatsapp_active', false)) {
            $sentViaAny = $this->sendViaWhatsApp($whatsAppService, $shortUrl, $evaluationLink) || $sentViaAny;
        }

        if ($sentViaAny) {
            $shortLink->update(['sent' => true]);
        }
    }

    private function sendViaSms(string $shortUrl, ?string $evaluationLink): bool
    {
        $smsResponse = $evaluationLink !== null
            ? app(TaqnyatSmsService::class)->sendPdfLinkWithEvaluation($this->phone, $shortUrl, $evaluationLink)
            : app(TaqnyatSmsService::class)->sendPdfLink($this->phone, $shortUrl);

        $sent = is_array($smsResponse)
            && isset($smsResponse['statusCode'])
            && (int) $smsResponse['statusCode'] === 201;

        if (! $sent) {
            Log::warning('SendInvoiceLinkJob: SMS send failed', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'response' => $smsResponse,
            ]);
        }

        return $sent;
    }

    private function sendViaWhatsApp(WhatsAppConfirmationService $whatsAppService, string $shortUrl, ?string $evaluationLink): bool
    {
        $templateName = Setting::where('key', 'invoice_whatsapp_template_name')->value('value');

        if (empty($templateName)) {
            Log::warning('SendInvoiceLinkJob: WhatsApp send skipped — invoice_whatsapp_template_name is not set', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
            ]);
            return false;
        }

        // The approved template is bilingual with 4 DISTINCT variables, not 2 reused ones: {{1}}/{{2}} for
        // the Arabic section, {{3}}/{{4}} for the English section repeating the same two links — deliberate,
        // so neither language's slot is ever filled by assuming the other's position. Meta requires every
        // declared variable to be filled, so without an evaluation link this template can't be sent at all
        // (there's no sensible value for {{2}}/{{4}}) — WhatsApp is skipped in that case; SMS still falls
        // back to its own, separate invoice-only message independently.
        if ($evaluationLink === null) {
            Log::warning('SendInvoiceLinkJob: WhatsApp send skipped — template requires an evaluation link and none resolved', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
            ]);
            return false;
        }

        $bodyParameters = [$shortUrl, $evaluationLink, $shortUrl, $evaluationLink];

        try {
            $response = $whatsAppService->sendTemplateMessage($this->phone, $templateName, $bodyParameters);
        } catch (\Throwable $e) {
            Log::warning('SendInvoiceLinkJob: WhatsApp send failed', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        // A "skipped" body (whatsapp_send_messages_active is a separate, existing kill switch) is still a
        // 200/successful HTTP response — must not be counted as a genuine send.
        $sent = $response instanceof \Illuminate\Http\Client\Response
            && $response->successful()
            && $response->json('status') !== 'skipped';

        if (! $sent) {
            Log::warning('SendInvoiceLinkJob: WhatsApp send was not accepted', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'response' => $response instanceof \Illuminate\Http\Client\Response ? $response->json() : $response,
            ]);
        }

        return $sent;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::random(8);
        } while (ShortLink::where('code', $code)->exists());

        return $code;
    }
}
