<?php

namespace App\Services\InvoiceWhatsApp;

use App\Models\DirectAppointment;
use App\Models\Setting;
use App\Services\Evaluation\EvaluationLinkResolver;
use App\Services\WhatsApp\WhatsAppConfirmationService;
use Illuminate\Http\Client\Response;

/**
 * The one piece of the new, independent cycle that actually knows how to build and send an invoice WhatsApp
 * message — everything about "what goes in the template" lives here, not in the job. Reuses
 * WhatsAppConfirmationService (the shared Cloud API client every WhatsApp sender in this app already goes
 * through) and EvaluationLinkResolver (a general-purpose utility, not something owned by the old cycle) —
 * neither of those is "the old system" itself, just shared infrastructure underneath both cycles.
 *
 * Deliberately does NOT read invoice_whatsapp_use_template, invoice_send_whatsapp_active, or any other
 * setting belonging to the existing cycle — this is a genuinely separate, parallel path, controlled by its
 * own setting (new_invoice_whatsapp_active, checked by the controller before this is ever reached) and its
 * own template-name lookup.
 */
class InvoiceWhatsAppSenderService
{
    public function __construct(
        private readonly WhatsAppConfirmationService $whatsApp,
        private readonly \App\Services\Evaluation\EvaluationLinkDataFetcher $evaluationLinkDataFetcher,
    ) {}

    /**
     * @return array{success: bool, message_id?: string, error?: string, response?: mixed}
     */
    public function send(string $bookId, string $phone): array
    {
        $templateName = Setting::where('key', 'invoice_whatsapp_template_name')->value('value');

        if (empty($templateName)) {
            return ['success' => false, 'error' => 'invoice_whatsapp_template_name setting is not configured'];
        }

        // A real, Meta-approved template name is never "true"/"false"/"1"/"0" — seen in production as the
        // literal value Meta rejected with "(#132001) Template name does not exist", which happens when this
        // setting's row gets overwritten with a boolean-looking value (e.g. a toggle setting accidentally
        // updated instead of this one). Catching it here fails fast with an actionable message, instead of
        // reaching Meta first and coming back with a cryptic OAuth error that doesn't name the real cause.
        if (in_array(strtolower((string) $templateName), ['true', 'false', '1', '0'], true)) {
            return ['success' => false, 'error' => "invoice_whatsapp_template_name is misconfigured — its value is \"{$templateName}\", not a real template name"];
        }

        $pdfUrl = url('/api/invoice/' . $bookId . '/download');

        $orderType = DirectAppointment::where('book_id', $bookId)->latest('id')->value('order_type');

        $evaluation = $this->buildEvaluationLinkForInvoice($bookId, $orderType);

        // Same approved template as the existing cycle (it's the one real, Meta-approved template with a
        // Document header) — 2 body variables, the evaluation link once for each half of the bilingual text,
        // matching the template's real, confirmed shape. invoice_evaluation_active (see
        // buildEvaluationLinkForInvoice()) controls whether that link carries real tracking data or is just
        // the plain, un-tracked page — the template's own fixed 2-parameter structure is unaffected either
        // way, so this never risks a parameter-count mismatch with the already-approved template.
        $bodyParameters = collect([$evaluation['url'], $evaluation['url']])
            ->map(fn($value) => ['type' => 'text', 'text' => $value])
            ->all();

        $documentHeader = [
            'link'     => $pdfUrl,
            'filename' => "Invoice-{$bookId}.pdf",
        ];

        try {
            $response = $this->whatsApp->sendTemplateMessage($phone, $templateName, $bodyParameters, [], 'ar', $documentHeader);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'evaluation' => $evaluation];
        }

        $responseBody = $response instanceof Response ? $response->json() : $response;

        // A "skipped" body (whatsapp_send_messages_active is a separate, existing, shared kill switch) is
        // still a 200/successful HTTP response — must not be counted as a genuine send.
        $sent = $response instanceof Response
            && $response->successful()
            && ($responseBody['status'] ?? null) !== 'skipped';

        if (! $sent) {
            return ['success' => false, 'error' => 'Not accepted by Meta', 'response' => $responseBody, 'evaluation' => $evaluation];
        }

        $messageId = $responseBody['messages'][0]['id'] ?? null;

        if (! $messageId) {
            return ['success' => false, 'error' => 'Meta accepted the request but returned no message id', 'response' => $responseBody, 'evaluation' => $evaluation];
        }

        return ['success' => true, 'message_id' => $messageId, 'response' => $responseBody, 'evaluation' => $evaluation];
    }

    /**
     * invoice_evaluation_active (default on, preserving existing behavior) controls whether the link sent
     * alongside the invoice carries real, encrypted tracking data or is just the plain, un-tracked
     * evaluation page — never whether a link is sent at all, since the already-approved template's 2 body
     * parameters are fixed and this must never risk a parameter-count mismatch with it. When disabled, the
     * EvaluationLinkDataFetcher call (a live DY365 lookup) is skipped entirely, not just its result ignored.
     *
     * @return array{type: ?string, url: string, data: ?array, status: string, error: ?string}
     */
    private function buildEvaluationLinkForInvoice(string $bookId, ?string $orderType): array
    {
        if (! Setting::isActive('invoice_evaluation_active', true)) {
            return [
                'type'   => $orderType,
                'url'    => EvaluationLinkResolver::resolve($orderType),
                'data'   => null,
                'status' => 'skipped',
                'error'  => null,
            ];
        }

        try {
            $evaluationLinkData = $this->evaluationLinkDataFetcher->fetchForBookId($bookId);
            $fullUrl = EvaluationLinkResolver::buildWithEncryptedData($orderType, $evaluationLinkData);

            // Shortened before being sent — the encrypted data query param alone can run 300+ characters,
            // and Meta rejects the whole template send once the rendered body exceeds 1024 characters
            // (confirmed in production: #132005 "Translated text too long"). evaluation_data (tracked
            // separately, above) already carries the same fields for debugging, so nothing about the real
            // link is lost by storing the short one here instead of the long one it points to.
            return [
                'type'   => $orderType,
                'url'    => EvaluationLinkResolver::shorten($fullUrl, $bookId, null, $evaluationLinkData),
                'data'   => $evaluationLinkData,
                'status' => 'sent',
                'error'  => null,
            ];
        } catch (\Throwable $e) {
            // A failed fetch here (DY365 down, etc.) still falls back to a plain, working link rather than
            // blocking the invoice send over it.
            \Illuminate\Support\Facades\Log::warning('InvoiceWhatsAppSenderService: could not fetch evaluation link data, sending plain link', [
                'book_id' => $bookId,
                'error'   => $e->getMessage(),
            ]);

            return [
                'type'   => $orderType,
                'url'    => EvaluationLinkResolver::resolve($orderType),
                'data'   => null,
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ];
        }
    }
}
