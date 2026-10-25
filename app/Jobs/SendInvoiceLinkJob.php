<?php

namespace App\Jobs;

use App\Models\DirectAppointment;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Services\DY365\DyService;
use App\Services\Evaluation\EvaluationLinkResolver;
use App\Services\TaqnyatSmsService;
use App\Services\Telegram\TelegramService;
use App\Services\WhatsApp\WhatsAppConfirmationService;
use App\Services\WhatsApp\WhatsAppService;
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
 * previously sent a link to the customer with NO verification an invoice existed at all.
 *
 * The ShortLink record is now saved IMMEDIATELY, before DY365 is even asked, so there's always a durable
 * record that a send was attempted — even when the invoice doesn't exist yet. Only a confirmed, valid
 * DY365 response (invoice_generated = true) results in a message actually being sent:
 *   - invoice_generated = false, last_error = 'Invoice not generated yet' — DY365 has nothing yet. Picked
 *     up by RetryPendingInvoiceLinksCommand, which runs daily and resends once an invoice does exist.
 *   - invoice_generated = true, sent = false — the invoice exists, but SMS/WhatsApp sending itself failed
 *     (already alerted via Telegram in whichever channel failed). The daily retry job intentionally leaves
 *     these alone, since the problem isn't a missing invoice.
 *   - sent = true — fully delivered.
 *
 * Which channel(s) actually send is controlled by two independent settings — both can be on at once:
 *   - invoice_send_sms_active (default: ON — this preserves the endpoint's existing behavior)
 *   - invoice_send_whatsapp_active (default: OFF — no approved WhatsApp template exists yet; turning this
 *     on before invoice_whatsapp_template_name holds a real, Meta-approved template name will just log a
 *     failed send, not crash anything)
 * The WhatsApp template name itself is NOT hardcoded — it's read from the invoice_whatsapp_template_name
 * setting, so a newly-approved template can be wired in by changing a setting value, not by a code change.
 *   - invoice_whatsapp_use_template (default: ON) — whether the WhatsApp send above uses the approved
 *     template or a plain freeform text message instead (see sendViaWhatsApp()'s own docblock for the
 *     trade-off between the two).
 *
 * Sent alongside the invoice link in both channels: the appointment's evaluation/rating link
 * (EvaluationLinkResolver, keyed off order_type — always resolves to a real link, falling back to the sales
 * evaluation page when order_type is missing or unrecognized, so there's always something valid to send).
 * This is IN ADDITION TO the separate evaluation WhatsApp message
 * EvaluationMessageController::createAndSendEvaluation() already sends elsewhere — that one is unchanged
 * and keeps sending on its own.
 */
class SendInvoiceLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $bookId,
        private readonly string $phone,
        private readonly bool $forceResend = false,
    ) {}

    public function handle(DyService $dyService, TaqnyatSmsService $smsService, WhatsAppConfirmationService $whatsAppService, WhatsAppService $whatsAppPlainService): void
    {
        $pdfUrl = url('/api/invoice/' . $this->bookId . '/download');

        $shortLink = ShortLink::where('book_id', $this->bookId)->latest('id')->first();

        if (! $shortLink) {
            // Saved immediately, before DY365 is even asked — previously nothing was saved at all until an
            // invoice was confirmed, so a book_id with no invoice yet left no record anywhere that a link
            // had even been attempted. phone is persisted here specifically so
            // RetryPendingInvoiceLinksCommand can resend later without it as a job parameter.
            $shortLink = ShortLink::create([
                'book_id' => $this->bookId,
                'phone'   => $this->phone,
                'code'    => $this->generateUniqueCode(),
                'url'     => $pdfUrl,
                'sent'    => false,
                'invoice_generated' => false,
            ]);
        } elseif (empty($shortLink->phone)) {
            // an existing record from before this phone column existed, or created without one for some
            // other reason — backfill it so a retry has somewhere to send to
            $shortLink->update(['phone' => $this->phone]);
        }

        if ($shortLink->sent && ! $this->forceResend) {
            Log::info('SendInvoiceLinkJob: link already sent previously, skipping', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
            ]);
            return;
        }

        try {
            $response = $dyService->getOrCreateInvoiceByBookId(['bookId' => $this->bookId]);
        } catch (\Throwable $e) {
            Log::warning('SendInvoiceLinkJob: DY365 call failed, no link sent', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);

            $shortLink->update(['invoice_generated' => false, 'last_error' => 'DY365 call failed: ' . $e->getMessage()]);

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

            $shortLink->update(['invoice_generated' => false, 'last_error' => 'Invoice not generated yet']);

            return;
        }

        $shortLink->update(['invoice_generated' => true]);

        $shortUrl = url('/' . $shortLink->code);

        $orderType = DirectAppointment::where('book_id', $this->bookId)->latest('id')->value('order_type');
        $evaluationLink = EvaluationLinkResolver::resolve($orderType);

        $sentViaAny = false;

        if (Setting::isActive('invoice_send_sms_active', true)) {
            $sentViaAny = $this->sendViaSms($shortUrl, $evaluationLink) || $sentViaAny;
        }

        if (Setting::isActive('invoice_send_whatsapp_active', false)) {
            $sentViaAny = $this->sendViaWhatsApp($whatsAppService, $whatsAppPlainService, $shortUrl, $evaluationLink) || $sentViaAny;
        }

        if ($sentViaAny) {
            // the invoice exists AND the message is out — last_error cleared, there's nothing left unresolved
            $shortLink->update(['sent' => true, 'last_error' => null]);
        } else {
            // invoice_generated stays true: DY365 does have the invoice, so the daily retry job (which only
            // looks at invoice_generated === false) correctly leaves this one alone — a send failure here is
            // an SMS/WhatsApp problem, not an "invoice not generated yet" problem, and already went to
            // Telegram via alertTelegram() in whichever channel failed.
            $shortLink->update(['last_error' => 'Invoice found, but sending failed on every enabled channel']);
        }
    }

    private function sendViaSms(string $shortUrl, string $evaluationLink): bool
    {
        // EvaluationLinkResolver always resolves to a real link now (falls back to the sales evaluation
        // page for a null/unrecognized order_type), so this always has a genuine evaluation link to include.
        $smsResponse = app(TaqnyatSmsService::class)->sendPdfLinkWithEvaluation($this->phone, $shortUrl, $evaluationLink);

        $sent = is_array($smsResponse)
            && isset($smsResponse['statusCode'])
            && (int) $smsResponse['statusCode'] === 201;

        if (! $sent) {
            Log::warning('SendInvoiceLinkJob: SMS send failed', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'response' => $smsResponse,
            ]);

            $this->alertTelegram('SMS', 'Send failed', ['response' => $smsResponse]);
        }

        return $sent;
    }

    /**
     * Which WhatsApp path to use is controlled by invoice_whatsapp_use_template (default: ON — this is the
     * approved, Meta-compliant path, so a missing setting row must never silently fall back to the other
     * one):
     *   - ON:  the approved template (invoice_whatsapp_template_name), 4 variables, includes the
     *          evaluation link — works outside the 24-hour customer-service window, since it's pre-approved.
     *   - OFF: a plain freeform text message (no template at all) — the older message wording, invoice
     *          link only, no evaluation link. WhatsApp Business API restricts freeform/session messages to
     *          within 24 hours of the customer's last message to the business; outside that window Meta
     *          rejects it, so this path can fail for a reason the template path never would.
     */
    private function sendViaWhatsApp(WhatsAppConfirmationService $whatsAppService, WhatsAppService $whatsAppPlainService, string $shortUrl, string $evaluationLink): bool
    {
        return Setting::isActive('invoice_whatsapp_use_template', true)
            ? $this->sendViaWhatsAppTemplate($whatsAppService, $shortUrl, $evaluationLink)
            : $this->sendViaWhatsAppFreeform($whatsAppPlainService, $shortUrl);
    }

    private function sendViaWhatsAppTemplate(WhatsAppConfirmationService $whatsAppService, string $shortUrl, string $evaluationLink): bool
    {
        $templateName = Setting::where('key', 'invoice_whatsapp_template_name')->value('value');

        if (empty($templateName)) {
            Log::warning('SendInvoiceLinkJob: WhatsApp send skipped — invoice_whatsapp_template_name is not set', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
            ]);

            $this->alertTelegram('WhatsApp', 'Skipped — invoice_whatsapp_template_name setting is not configured');

            return false;
        }

        // The approved template is bilingual with 4 DISTINCT variables, not 2 reused ones: {{1}}/{{2}} for
        // the Arabic section, {{3}}/{{4}} for the English section repeating the same two links — deliberate,
        // so neither language's slot is ever filled by assuming the other's position.
        //
        // Meta's Cloud API rejects a "parameters" array of raw strings outright ("expected: '[object,
        // null]'") — every parameter must be an object. Every OTHER existing caller of
        // sendTemplateMessage() (WhatsAppController, etc.) already builds this shape themselves; this job
        // was the first to pass raw strings instead, which is what actually triggered the error — the
        // service method itself is a plain pass-through and needed no change.
        $bodyParameters = collect([$shortUrl, $evaluationLink, $shortUrl, $evaluationLink])
            ->map(fn($value) => ['type' => 'text', 'text' => $value])
            ->all();

        try {
            $response = $whatsAppService->sendTemplateMessage($this->phone, $templateName, $bodyParameters);
        } catch (\Throwable $e) {
            Log::warning('SendInvoiceLinkJob: WhatsApp send failed', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);

            $this->alertTelegram('WhatsApp', 'Send threw an exception', ['error' => $e->getMessage(), 'template' => $templateName]);

            return false;
        }

        // A "skipped" body (whatsapp_send_messages_active is a separate, existing kill switch) is still a
        // 200/successful HTTP response — must not be counted as a genuine send.
        $sent = $response instanceof \Illuminate\Http\Client\Response
            && $response->successful()
            && $response->json('status') !== 'skipped';

        if (! $sent) {
            $responseForLog = $response instanceof \Illuminate\Http\Client\Response ? $response->json() : $response;

            Log::warning('SendInvoiceLinkJob: WhatsApp send was not accepted', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'response' => $responseForLog,
            ]);

            $this->alertTelegram('WhatsApp', 'Not accepted by Meta', ['template' => $templateName, 'response' => $responseForLog]);
        }

        return $sent;
    }

    private function sendViaWhatsAppFreeform(WhatsAppService $whatsAppPlainService, string $shortUrl): bool
    {
        $text = "فاتورتك جاهزة ✅ يمكنك تحميلها من الرابط التالي: {$shortUrl} شكراً لاختياركم نقي.";

        try {
            $result = $whatsAppPlainService->sendTextMessage($this->phone, $text);
        } catch (\Throwable $e) {
            Log::warning('SendInvoiceLinkJob: WhatsApp freeform send threw an exception', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'error' => $e->getMessage(),
            ]);

            $this->alertTelegram('WhatsApp (freeform)', 'Send threw an exception', ['error' => $e->getMessage()]);

            return false;
        }

        // sendTextMessage() always returns a plain array (unlike sendTemplateMessage(), which returns the
        // full Response object): either the kill-switch's skipped body, or the raw $response->json() on
        // both success and failure — so "sent" means a genuine WhatsApp message id came back, not just that
        // the HTTP call itself didn't throw.
        $sent = is_array($result) && isset($result['messages']) && ($result['status'] ?? null) !== 'skipped';

        if (! $sent) {
            Log::warning('SendInvoiceLinkJob: WhatsApp freeform send was not accepted', [
                'book_id' => $this->bookId,
                'phone' => $this->phone,
                'response' => $result,
            ]);

            $this->alertTelegram('WhatsApp (freeform)', 'Not accepted', ['response' => $result]);
        }

        return $sent;
    }

    /**
     * Every SMS/WhatsApp send failure here also goes to Telegram, not just the log file — so an unsent
     * invoice link is noticed quickly rather than only showing up if someone goes looking through
     * storage/logs/laravel.log. Scoped to SMS/WhatsApp send outcomes specifically (not the earlier DY365
     * checks, which prevent either channel from being attempted at all, not a send failure on one of them).
     */
    private function alertTelegram(string $channel, string $reason, array $context = []): void
    {
        $lines = [
            "🚨 Invoice link {$channel} not sent",
            '',
            "book_id: {$this->bookId}",
            "phone: {$this->phone}",
            "reason: {$reason}",
        ];

        foreach ($context as $key => $value) {
            $lines[] = "{$key}: " . (is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE));
        }

        TelegramService::send(implode("\n", $lines));
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::random(8);
        } while (ShortLink::where('code', $code)->exists());

        return $code;
    }
}
