<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $url = 'https://graph.facebook.com/v17.0/109567455104882/messages';
    protected string $templateUrl = 'https://graph.facebook.com/v17.0/109567455104882/message_templates';

    protected string $token;



    public function __construct()
    {
        $this->token = config('services.whatsapp.token', '');
    }

    public function sendTemplateMessage(string $to, string $templateName, array $parameters = [], string $lang = 'ar')
    {
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $to,
            "type"              => "template",
            "template"          => [
                "name"     => $templateName,
                "language" => [
                    "code" => $lang
                ],
                "components" => [
                    [
                        "type"       => "body",
                        "parameters" => $parameters
                    ]
                ]
            ]
        ];

        $response = Http::withToken($this->token)
            ->post($this->url, $payload);
        // dd($response->json());
        return $response->json();
    }

    public function getTemplates()
    {
        $response = Http::withToken($this->token)
            ->get($this->templateUrl);

        return $response->json();
    }

    /**
     * Sends a plain free-form text message — valid here because this is
     * used as a direct reply within the customer's own active session
     * (they just messaged us via a button click), which WhatsApp allows
     * outside of pre-approved templates. Do NOT use this for
     * business-initiated messages outside an active session — those
     * still require an approved template, same as sendTemplateMessage().
     */
    public function sendTextMessage(string $to, string $text): mixed
    {
        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type"    => "individual",
            "to"                => $to,
            "type"              => "text",
            "text"              => [
                "body" => $text,
            ],
        ];

        $response = Http::withToken($this->token)
            ->post($this->url, $payload);

        return $response->json();
    }

    // ──────────────────────────────────────────
    // Order Lead WhatsApp Flow
    // ──────────────────────────────────────────
    /**
     * Send Question 1 (booking readiness) to the customer.
     *
     * Buttons: 3 Quick Reply (payload = orderNumber + choice label)
     */
    public function sendQuestion1(string $mobile, string $orderNumber, string $customerName, string $product): mixed
    {
        $bodyParameters = [
            ['type' => 'text', 'text' => $product],
        ];

        // Payload format: {orderNumber}#{choiceId}#{choiceText}
        // choiceId (Q1A / Q1B / Q1C) is used server-side to resolve the score reliably
        $buttonParameters = [
            ['payload' => "{$orderNumber}#Q1A#طلب حجز"],
            ['payload' => "{$orderNumber}#Q1B# استفسار  "],
        ];

        $service = app(\App\Services\WhatsApp\WhatsAppConfirmationService::class);
        return    $response = $service->sendTemplateMessage(
            $mobile,
            'lead_question_1',
            $bodyParameters,
            $buttonParameters,
            'ar'
        );
    }

    /**
     * Send Question 2 — preferred contact time.
     *
     * Body {{1}} = customer name
     * Body {{2}} = product name
     * Body {{3}} = order number
     * Buttons: 4 Quick Reply (payload = orderNumber + choice label)
     */
    public function sendQuestion2(string $mobile, string $orderNumber, string $product): mixed
    {
        $bodyParameters = [];
        // Payload format: {orderNumber}#{choiceId}#{choiceText}
        // choiceId (Q2A / Q2B / Q2C / Q2D) is used server-side to resolve the score reliably
        $buttonParameters = [
            ['payload' => "{$orderNumber}#Q2A#أقرب وقت"],
            ['payload' => "{$orderNumber}#Q2B#خلال ساعة"],
            ['payload' => "{$orderNumber}#Q2C#خلال اليوم"],
        ];
        $service = app(\App\Services\WhatsApp\WhatsAppConfirmationService::class);
        $response = $service->sendTemplateMessage(
            $mobile,
            'lead_question_2',
            $bodyParameters,
            $buttonParameters,
            'ar'
        );


        return $response;
    }

    public function sendSalesEvaluation(string $mobile, $template): mixed
    {
        $bodyParameters = [];
        $buttonParameters = [];
        $service = app(\App\Services\WhatsApp\WhatsAppConfirmationService::class);
        $response = $service->sendTemplateMessage(
            $mobile,
            $template,
            $bodyParameters,
            $buttonParameters,
            'ar'
        );


        return $response;
    }
    /**
     * Parse an inbound webhook payload (Meta / 360dialog format).
     * Returns ['mobile' => ..., 'message_body' => ...] or null.
     *
     * @param  array  $payload  Raw decoded webhook body
     * @return array{mobile: string, message_body: string}|null
     */
    public function parseInbound(array $payload): ?array
    {
        try {
            $message = data_get($payload, 'entry.0.changes.0.value.messages.0');
            if (! $message) return null;

            $mobile = data_get($message, 'from');
            if (! $mobile) return null;
            $mobile = str_starts_with($mobile, '+') ? $mobile : '+' . $mobile;

            // context.id = the message ID the customer is replying TO
            // This is the most reliable way to know which question was answered
            $contextMessageId = data_get($message, 'context.id');

            $type = data_get($message, 'type');

            if ($type === 'interactive') {
                $buttonPayload = data_get($message, 'interactive.button_reply.id');
                $buttonTitle   = data_get($message, 'interactive.button_reply.title');
                if (! $buttonPayload) return null;

                return [
                    'mobile'             => $mobile,
                    'message_body'       => $buttonTitle,
                    'payload'            => $buttonPayload,
                    'context_message_id' => $contextMessageId,
                ];
            }

            if ($type === 'text') {
                $body = trim(data_get($message, 'text.body', ''));
                if (! $body) return null;

                return [
                    'mobile'             => $mobile,
                    'message_body'       => $body,
                    'payload'            => null,
                    'context_message_id' => $contextMessageId,
                ];
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning('WhatsApp parseInbound failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
