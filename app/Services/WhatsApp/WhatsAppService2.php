<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService2
{
    private string $token;
    private string $url;

    public function __construct()
    {
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->token   = config('services.whatsapp.token', '');
        $this->url     = "https://graph.facebook.com/v18.0/{$phoneNumberId}/messages";
    }

    // ──────────────────────────────────────────────────────────────────
    // Public API
    // ──────────────────────────────────────────────────────────────────

    /**
     * Send Question 1 — booking readiness.
     *
     * Body {{1}} = customer name
     * Body {{2}} = product name
     * Body {{3}} = order number
     * Buttons: 3 Quick Reply (payload = orderNumber + choice label)
     */
    public function sendQuestion1(
        string $mobile,
        string $orderNumber,
        string $customerName,
        string $product
    ): mixed {
        $bodyParameters = [
            ['type' => 'text', 'text' => $customerName],
            ['type' => 'text', 'text' => $product],
            ['type' => 'text', 'text' => $orderNumber],
        ];

        $buttonParameters = [
            ['payload' => "{$orderNumber}1- جاهز للحجز والتركيب"],
            ['payload' => "{$orderNumber}2- أحتاج أتأكد أنه مناسب لي"],
            ['payload' => "{$orderNumber}3- عندي استفسار قبل الطلب"],
        ];

        return $this->sendTemplateMessage(
            $mobile,
            'lead_question_1',
            $bodyParameters,
            $buttonParameters
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
    public function sendQuestion2(
        string $mobile,
        string $orderNumber,
        string $customerName,
        string $product
    ): mixed {
        $bodyParameters = [
            ['type' => 'text', 'text' => $customerName],
            ['type' => 'text', 'text' => $product],
            ['type' => 'text', 'text' => $orderNumber],
        ];

        $buttonParameters = [
            ['payload' => "{$orderNumber}1- أقرب وقت"],
            ['payload' => "{$orderNumber}2- خلال ساعة"],
            ['payload' => "{$orderNumber}3- خلال اليوم"],
            ['payload' => "{$orderNumber}4- واتساب فقط"],
        ];

        return $this->sendTemplateMessage(
            $mobile,
            'lead_question_2',
            $bodyParameters,
            $buttonParameters
        );
    }

    /**
     * Parse an inbound webhook payload (Meta Cloud API format).
     *
     * Handles Quick Reply button taps (type = interactive) and plain text.
     *
     * @return array{mobile: string, message_body: string, payload: string|null}|null
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

    // ──────────────────────────────────────────────────────────────────
    // Core send — matches your working sendTemplateMessage signature
    // ──────────────────────────────────────────────────────────────────

    public function sendTemplateMessage(
        string $to,
        string $templateName,
        array  $bodyParameters   = [],
        array  $buttonParameters = [],
        string $lang             = 'ar'
    ): mixed {
        $components = [];

        // Body component
        if (! empty($bodyParameters)) {
            $components[] = [
                'type'       => 'body',
                'parameters' => $bodyParameters,
            ];
        }

        // One component entry per button (index must be string)
        if (! empty($buttonParameters)) {
            foreach ($buttonParameters as $index => $button) {
                $components[] = [
                    'type'       => 'button',
                    'sub_type'   => $button['sub_type'] ?? 'quick_reply',
                    'index'      => (string) $index,
                    'parameters' => [
                        [
                            'type'    => 'payload',
                            'payload' => $button['payload'],
                        ],
                    ],
                ];
            }
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $lang],
                'components' => $components,
            ],
        ];

        if (! $this->token) {
            Log::info("[WhatsApp stub → {$to}] template: {$templateName}");
            return ['status' => 'stub'];
        }

        // DEBUG — log the exact payload being sent (remove after fixing)
        Log::debug('[WhatsApp outgoing payload]', $payload);

        $response = Http::withToken($this->token)
            ->post($this->url, $payload);

        if ($response->failed()) {
            Log::error('[WhatsApp send failed]', [
                'mobile'          => $to,
                'template'        => $templateName,
                'status'          => $response->status(),
                'response_full'   => $response->body(),
                'request_payload' => json_encode($payload),
            ]);
            $response->throw();
        }

        return $response->json();
    }
}
