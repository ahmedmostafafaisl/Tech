<?php

namespace App\Services\WhatsApp;

use App\Services\Telegram\TelegramService;
use Illuminate\Support\Facades\Http;

class WhatsAppConfirmationService
{
    protected string $url = 'https://graph.facebook.com/v17.0/109567455104882/messages';
    protected string $templateUrl = 'https://graph.facebook.com/v17.0/109567455104882/message_templates';

    protected string $token;

    public function __construct()
    {
        $this->token = config('services.whatsapp.token');
    }

    public function sendTemplateMessage(string $to, string $templateName, array $bodyParameters = [], array $buttonParameters = [], string $lang = 'ar')
    {
        if (!\App\Models\Setting::isActive('whatsapp_send_messages_active', true)) {
            \Illuminate\Support\Facades\Log::info('WhatsApp send skipped — whatsapp_send_messages_active is off.', [
                'to' => $to,
                'template' => $templateName,
            ]);

            // Every caller of this method (EvaluationMessageController, ResendPreAppointmentMessages,
            // WhatsAppController, and others) calls ->json()/->successful() on the result, assuming it's
            // always a real Illuminate\Http\Client\Response — this used to return a plain array here
            // instead, which crashed every one of those callers with "Call to a member function json() on
            // array" the moment this kill switch was turned off. A real Response object (implements
            // ArrayAccess, so $response['messages'] still works for the one caller that uses that instead
            // of ->json()) keeps every existing caller working without changing any of them.
            return new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(200, [], json_encode([
                    'status' => 'skipped',
                    'reason' => 'whatsapp_send_messages_active is off',
                ]))
            );
        }

        $components = [];

        // body parameters
        if (!empty($bodyParameters)) {
            $components[] = [
                "type"       => "body",
                "parameters" => $bodyParameters
            ];
        }

        // button parameters
        if (!empty($buttonParameters)) {
            foreach ($buttonParameters as $index => $button) {
                $components[] = [
                    "type"       => "button",
                    "sub_type"   => $button['sub_type'] ?? 'quick_reply',
                    "index"      => (string) $index,
                    "parameters" => [
                        [
                            "type"    => "payload",
                            "payload" => $button['payload']
                        ]
                    ]
                ];
            }
        }

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
                "components" => $components
            ]
        ];

        $response = Http::withToken($this->token)
            ->post($this->url, $payload);

        return $response;
    }
}
