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

        // OrderTypeId, book_id, tech_user_name and district encrypted into the link itself (see
        // EvaluationLinkResolver::buildWithEncryptedData()) — a failed fetch here (DY365 down, etc.) still
        // falls back to a plain, working link rather than blocking the invoice send over it.
        try {
            $evaluationLinkData = $this->evaluationLinkDataFetcher->fetchForBookId($bookId);
            $evaluationLink = EvaluationLinkResolver::buildWithEncryptedData($orderType, $evaluationLinkData);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('InvoiceWhatsAppSenderService: could not fetch evaluation link data, sending plain link', [
                'book_id' => $bookId,
                'error'   => $e->getMessage(),
            ]);

            $evaluationLink = EvaluationLinkResolver::resolve($orderType);
        }

        // Same approved template as the existing cycle (it's the one real, Meta-approved template with a
        // Document header) — 2 body variables, the evaluation link once for each half of the bilingual text,
        // matching the template's real, confirmed shape.
        $bodyParameters = collect([$evaluationLink, $evaluationLink])
            ->map(fn($value) => ['type' => 'text', 'text' => $value])
            ->all();

        $documentHeader = [
            'link'     => $pdfUrl,
            'filename' => "Invoice-{$bookId}.pdf",
        ];

        try {
            $response = $this->whatsApp->sendTemplateMessage($phone, $templateName, $bodyParameters, [], 'ar', $documentHeader);
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }

        $responseBody = $response instanceof Response ? $response->json() : $response;

        // A "skipped" body (whatsapp_send_messages_active is a separate, existing, shared kill switch) is
        // still a 200/successful HTTP response — must not be counted as a genuine send.
        $sent = $response instanceof Response
            && $response->successful()
            && ($responseBody['status'] ?? null) !== 'skipped';

        if (! $sent) {
            return ['success' => false, 'error' => 'Not accepted by Meta', 'response' => $responseBody];
        }

        $messageId = $responseBody['messages'][0]['id'] ?? null;

        if (! $messageId) {
            return ['success' => false, 'error' => 'Meta accepted the request but returned no message id', 'response' => $responseBody];
        }

        return ['success' => true, 'message_id' => $messageId, 'response' => $responseBody];
    }
}
