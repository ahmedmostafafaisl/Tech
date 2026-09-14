<?php

namespace App\Services\Lead;

use App\Models\OrderLead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private const PRIORITY_LABEL = [
        'hot'  => '🔥 HOT',
        'warm' => '⚡ WARM',
        'cold' => '🧊 COLD',
    ];

    /**
     * Notify the sales team that a lead has been fully scored.
     *
     * @param  OrderLead  $lead
     * @param  array{q1_score: int, q2_score: int, total_score: int, priority: string}  $breakdown
     */
    public function notifySalesTeam(OrderLead $lead, array $breakdown): void
    {
        $message = $this->buildMessage($lead, $breakdown);

        $this->notifySlack($message);
        $this->notifyWhatsApp($message);
    }

    private function buildMessage(OrderLead $lead, array $breakdown): string
    {
        $label = self::PRIORITY_LABEL[$breakdown['priority']] ?? strtoupper($breakdown['priority']);

        return implode("\n", [
            "{$label} — New scored lead",
            '',
            "Order:    {$lead->order_number}",
            "Customer: {$lead->customer_name}",
            "Mobile:   {$lead->mobile_number}",
            "Product:  " . ($lead->product ?? '—'),
            '',
            "Score: {$breakdown['total_score']}  (Q1: {$breakdown['q1_score']}  +  Q2: {$breakdown['q2_score']})",
        ]);
    }

    /**
     * Post to a Slack Incoming Webhook.
     * Set SLACK_WEBHOOK_URL in .env.
     */
    private function notifySlack(string $message): void
    {
        $url = config('services.slack.webhook_url');

        if (! $url) {
            Log::info('[Notify → Slack stub] ' . $message);
            return;
        }

        Http::post($url, ['text' => $message])->throw();
    }

    /**
     * Send a WhatsApp notification to the sales team number.
     * Set SALES_WHATSAPP_NUMBER in .env.
     */
    private function notifyWhatsApp(string $message): void
    {
        $salesNumber = config('services.whatsapp.sales_number');

        if (! $salesNumber) {
            Log::info('[Notify → WhatsApp stub] ' . $message);
            return;
        }

        app(WhatsAppService::class)->sendRaw($salesNumber, $message);
    }
}
