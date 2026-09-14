<?php

namespace App\Http\Controllers\Api\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreOrderRequest;
use App\Services\Lead\LeadService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly LeadService     $leadService,
        private readonly WhatsAppService $whatsAppService,
    ) {}

    /**
     * POST /api/orders
     *
     * Create a lead and send Question 1 via WhatsApp.
     * Save the returned message ID to match future inbound replies.
     */
    public function store(StoreOrderRequest $request)
    {

        $lead = $this->leadService->createLead($request->validated());

        $q1 = $this->whatsAppService->sendQuestion1(
            $lead->mobile_number,
            $lead->order_number,
            $lead->customer_name ?? 'عميلنا العزيز',
            $lead->product       ?? 'استفسارك',
        );

        $response = $q1->json();
        if (
            isset($response['messages'][0]['message_status']) &&
            $response['messages'][0]['message_status'] === 'accepted'
        ) {
            // Save the WhatsApp message ID for Q1
            $messageId = $this->leadService->extractMessageId($response);

            if ($messageId) {
                $this->leadService->saveMessageId($lead, 1, $messageId);
            }

            $this->leadService->updateStatus($lead, 'q1_sent');

            return response()->json([
                'message'    => 'Question 1 sent successfully.',
                'lead_id'    => $lead->id,
                'message_id' => $messageId,
                'data'       => $response,
            ], 201);
        }

        return response()->json([
            'message' => 'Failed to send WhatsApp message.',
            'error'   => $response,
        ], 422);
    }
}
