<?php

namespace App\Services\Lead;

use App\Models\OrderLead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LeadService
{
    /**
     * Create a new lead from an incoming order.
     *
     * @param  array{customer_name: string, mobile_number: string, product?: string}  $data  (order_number auto-generated)
     */
    public function createLead(array $data): OrderLead
    {

        return OrderLead::create([

            'customer_name' => $data['customer_name'] ?? null,
            'mobile_number' => $data['mobile_number'] ?? null,
            'product'       => $data['product'] ?? null,
            'rec_id'        => $data['rec_id'] ?? null,
            'status'        => 'pending',
        ]);
    }

    /**
     * Find a lead by order number.
     */
    public function getByOrderNumber(string $orderNumber): ?OrderLead
    {
        return OrderLead::where('order_number', $orderNumber)->first();
    }

    /**
     * Find the most recent active lead for a mobile number.
     * Used to route inbound WhatsApp replies to the right lead.
     */
    public function getByMobile(string $mobile): ?OrderLead
    {
        return OrderLead::where('mobile_number', $mobile)
            ->latest()
            ->first();
    }

    /**
     * Find a lead by its UUID, optionally eager-loading responses.
     */
    public function getById(string $id, bool $withResponses = false): ?OrderLead
    {
        $query = OrderLead::query();

        if ($withResponses) {
            $query->with('responses');
        }

        return $query->find($id);
    }

    /**
     * List leads with optional filters, sorted by score descending.
     *
     * @param  array{priority?: string, status?: string, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = OrderLead::byScore();

        if (! empty($filters['priority'])) {
            $query->priority($filters['priority']);
        }

        if (! empty($filters['status'])) {
            $query->status($filters['status']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Advance the lead through the status state machine.
     */
    /**
     * Extract the WhatsApp message ID from a successful send response.
     * Response format: { "messages": [{ "id": "wamid.xxx" }] }
     */
    public function extractMessageId(array $response): ?string
    {
        return data_get($response, 'messages.0.id');
    }

    /**
     * Save the WhatsApp message ID for Q1 or Q2 on the lead.
     *
     * @param  OrderLead  $lead
     * @param  int        $questionNo  1 or 2
     * @param  string     $messageId   wamid returned by Meta
     */
    public function saveMessageId(OrderLead $lead, int $questionNo, string $messageId): OrderLead
    {
        $field = "q{$questionNo}_message_id";
        $lead->update([$field => $messageId]);
        return $lead->refresh();
    }

    /**
     * Advance the lead's status in the state machine.
     */
    public function updateStatus(OrderLead $lead, string $status): OrderLead
    {
        $lead->update(['status' => $status]);
        return $lead->refresh();
    }

    /**
     * Persist the final score and priority. Sets status to 'scored'.
     */
    public function applyScore(OrderLead $lead, int $totalScore, string $priority): OrderLead
    {
        $lead->update([
            'total_score' => $totalScore,
            'priority'    => $priority,
            'status'      => 'scored',
        ]);

        return $lead->refresh();
    }
}
