<?php

namespace App\Http\Controllers\Api\WhatsApp;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsApp\SendWhatsAppMessageRequest;
use App\Jobs\SendPreAppointmentMessageJob;
use App\Models\OrderLead;
use App\Models\PreAppointmentMessage;
use App\Models\User;
use App\Notifications\TechNotification;
use App\Services\DY365\DyService;
use App\Services\Lead\LeadService;
use App\Services\Lead\NotificationService;
use App\Services\Lead\ResponseService;
use App\Services\Lead\ScoringService;
use App\Services\Telegram\TelegramService;
use App\Services\WhatsApp\WhatsAppConfirmationService;
use App\Services\WhatsApp\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WhatsAppController extends Controller
{

    protected $dyService;


    public function __construct(
        private readonly LeadService         $leadService,
        private readonly ResponseService     $responseService,
        private readonly ScoringService      $scoringService,
        private readonly WhatsAppService     $whatsAppService,
        private readonly NotificationService $notificationService,

        DyService $dyService
    ) {
        $this->dyService = $dyService;
    }

    public function notify(SendWhatsAppMessageRequest $request, WhatsAppService $whatsapp)
    {

        $data = $request->validated();

        if ($data['notify_type'] === 'create') {
            if (str_contains($data['type'] ?? '', 'طارئة')) {
                $data['template'] = 'new_appointment_emergency_1';
                $parameters = [
                    ["type" => "text", "text" => $data['name']],
                    ["type" => "text", "text" => $data['type']],
                    ["type" => "text", "text" => $data['date']],
                ];
            } else {
                $data['template'] = 'new_appointment_2';
                $parameters = [
                    ["type" => "text", "text" => $data['name']],
                    ["type" => "text", "text" => $data['type']],
                    ["type" => "text", "text" => $data['date']],
                    ["type" => "text", "text" => $data['items']],
                ];
            }
        } elseif ($data['notify_type'] === 'update') {
            $data['template'] = 'appointment_modified';
            $parameters = [
                ["type" => "text", "text" => $data['name']],
                ["type" => "text", "text" => $data['type']],
                ["type" => "text", "text" => $data['date']],
            ];
        } elseif ($data['notify_type'] === 'confirmation') {
            // $data['template'] = 'pre_appointment_action_v3';
            $data['template'] = 'pre_appointment_action_v4';
            $parameters = [
                ["type" => "text", "text" => $data['name']],
                ["type" => "text", "text" => $data['type']],
                ["type" => "text", "text" => $data['date']],
                ["type" => "text", "text" => $data['items']],
            ];
        } elseif ($data['notify_type'] === 'replace') {
            try {
                $removedFromUser = User::where('tech_id', $data['worker_id'])->first();
                if ($removedFromUser && $removedFromUser->fcm_token) {
                    $removedFromUser->notify(new TechNotification(
                        "appointment is removed from you",
                        "Appointment Removed",
                        $data['notify_type'],
                        ['bookId' => $request->bookId ?? null, 'sales_order' => $request->bookId ?? null]
                    ));
                } else {
                    Log::warning('TechNotification (replace - removed): User not found or missing FCM token', [
                        'worker_id' => $data['worker_id'] ?? null,
                        'bookId' => $data['bookId'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('TechNotification (replace - removed) failed: ' . $e->getMessage(), [
                    'worker_id' => $data['worker_id'] ?? null,
                    'bookId' => $data['bookId'] ?? null,
                ]);
            }
            try {
                $addedForUser = User::where('tech_id', $data['to_worker'])->first();
                if ($addedForUser && $addedForUser->fcm_token) {
                    $addedForUser->notify(new TechNotification(
                        "new appointment added for you",
                        "New Appointment Added",
                        $data['notify_type'],
                        ['bookId' => $request->bookId ?? null, 'sales_order' => $request->bookId ?? null]
                    ));
                } else {
                    Log::warning('TechNotification (replace - added): User not found or missing FCM token', [
                        'to_worker' => $data['to_worker'] ?? null,
                        'bookId' => $data['bookId'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('TechNotification (replace - added) failed: ' . $e->getMessage(), [
                    'to_worker' => $data['to_worker'] ?? null,
                    'bookId' => $data['bookId'] ?? null,
                ]);
            }
            return response()->json(['message' => 'Replace notifications sent']);
        } else {
            return response()->json(['error' => 'Invalid notify type'], 400);
        }

        $today = now()->format('d/m/Y');
        if (isset($data['date']) && $data['date'] === $today) {    // start notification
            // ✅ Start notification — safely (even if it fails, continue)
            try {
                $user = User::where('tech_id', $data['worker_id'])->first();
                if ($user && $user->fcm_token) {
                    $user->notify(new TechNotification(
                        "You have a new appointment",
                        "New Appointment Received",
                        $data['notify_type'],
                        ['bookId' => $request->bookId ?? null, 'sales_order' => $request->bookId ?? null]
                    ));
                } else {
                    Log::warning('TechNotification: User not found or missing FCM token', [
                        'worker_id' => $data['worker_id'] ?? null,
                        'bookId' => $data['bookId'] ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                // Log error but continue WhatsApp message
                Log::error('TechNotification failed: ' . $e->getMessage(), [
                    'worker_id' => $data['worker_id'] ?? null,
                    'bookId' => $data['bookId'] ?? null,
                ]);
            }
        }

        // ✅ Continue WhatsApp message sending even if notification failed
        // end notification
        try {
            $response = $whatsapp->sendTemplateMessage(
                $data['phone'],
                $data['template'],
                $parameters,
                "ar"
            );

            // If WhatsApp API responded with error JSON
            if (isset($response['error'])) {
                return response()->json([
                    'error' => $response['error']
                ], 400);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            // Catch exceptions like network errors
            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => 'Exception',
                    'code' => $e->getCode(),
                ]
            ], 500);
        }
    }

    public function sendPreAppointmentMessage(Request $request, WhatsAppConfirmationService $whatsapp)
    {
        $rules = [
            'phone'          => 'required|string|min:8|max:20',
            'sales_order'    => 'required|string',
            'book_id'        => 'required|string',
            'appointment_id' => 'required',
            'worker_id'      => 'required',
            'customer_id'    => 'required',
            'name'           => 'required|string',
            'type'           => 'nullable|string',
            'date'           => 'required|date',
            'items'          => 'nullable|string',
            'lead_message'   => 'nullable|boolean',
        ];

        if ($request->boolean('lead_message')) {
            $rules['phone']          = 'nullable|string|min:8|max:20';
            $rules['sales_order']    = 'nullable|string';
            $rules['book_id']        = 'nullable|string';
            $rules['appointment_id'] = 'nullable';
            $rules['worker_id']      = 'nullable';
            $rules['customer_id']    = 'nullable';
            $rules['name']           = 'nullable|string';
            $rules['date']           = 'nullable|date';
            $rules['rec_id']         = 'required';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        //send lead messages
        if (!empty($request->input('lead_message'))) {
            $validated_data = ['customer_name' => $request->input('name'), 'mobile_number' => $request->input('phone'),  'product' => $request->input('items'), 'rec_id' => $request->input('rec_id')];
            $lead = $this->leadService->createLead($validated_data);

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


        $data = $validator->validated();
        $data['type'] = $data['type'] ?? 'Service';
        $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');
        $data['items'] = $data['items'] ?? '-';

        // ✅ Products (منتجات): skip both the DB save and the WhatsApp send
        // entirely — return a queued-looking response without persisting
        // or sending anything.
        if (trim($data['type']) === 'منتجات') {
            return response()->json([
                'status'  => true,
                'message' => 'Message queued successfully.',
                'data'    => [
                    'id'          => null,
                    'phone'       => $data['phone'],
                    'sales_order' => $data['sales_order'],
                    'queued'      => true,
                ],
            ]);
        }

        // ✅ 1. Save record before sending
        $message = PreAppointmentMessage::create($data);

        $bodyParameters = [
            ["type" => "text", "text" => $data['name']],
            ["type" => "text", "text" => $data['type']],
            ["type" => "text", "text" => $data['date']],
            ["type" => "text", "text" => $data['items']],
        ];

        $buttonParameters = [
            ["payload" => "{$data['appointment_id']}Yes"],
            ["payload" => "{$data['appointment_id']}Reschedule"],
            ["payload" => "{$data['appointment_id']}Not interested"],
        ];

        try {
            $response = $whatsapp->sendTemplateMessage(
                $data['phone'],
                'pre_appointment_action_v3',
                // 'pre_appointment_action_v4',
                $bodyParameters,
                $buttonParameters,
                'ar'
            );


            // ✅ Check if message was accepted
            $isSent = isset($response['messages'][0]['message_status']) &&
                $response['messages'][0]['message_status'] === 'accepted';

            $messageId = $response['messages'][0]['id'] ?? null;

            // ✅ Update record after sending
            $message->update([
                'is_sent'    => $isSent,
                'message_id' => $messageId,
                'response'   => json_encode($response),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Message sent successfully',
                'data'    => [
                    'phone' => $data['phone'],
                    'sales_order' => $data['sales_order'],
                    'is_sent' => $isSent,
                    'response' => $response,
                ],
            ]);
        } catch (\Throwable $e) {
            // ❌ Update failed status
            $message->update([
                'is_sent' => false,
                'response' => $e->getMessage(),
            ]);

            Log::error('Failed to send WhatsApp message: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Failed to send WhatsApp message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendPreAppointmentMessage2(Request $request, WhatsAppConfirmationService $whatsapp)
    {
        $validator = Validator::make($request->all(), [
            'phone'          => 'required|string|min:8|max:20',
            'sales_order'    => 'required|string',
            'book_id'        => 'required|string',
            'appointment_id' => 'required',
            'worker_id'      => 'required',
            'customer_id'    => 'required',
            'name'           => 'required|string',
            'type'           => 'nullable|string',
            'date'           => 'required|date',
            'items'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data          = $validator->validated();
        $data['type']  = $data['type']  ?? 'Service';
        $data['date']  = Carbon::parse($data['date'])->format('Y-m-d');
        $data['items'] = $data['items'] ?? '-';

        // ✅ Save record first
        $message = PreAppointmentMessage::create($data);

        $bodyParameters = [
            ["type" => "text", "text" => $data['name']],
            ["type" => "text", "text" => $data['type']],
            ["type" => "text", "text" => $data['date']],
            ["type" => "text", "text" => $data['items']],
        ];

        $buttonParameters = [
            ["payload" => "{$data['appointment_id']}Yes"],
            ["payload" => "{$data['appointment_id']}Reschedule"],
            ["payload" => "{$data['appointment_id']}Not interested"],
        ];

        // ✅ Dispatch to queue
        SendPreAppointmentMessageJob::dispatch($message, $bodyParameters, $buttonParameters)
            ->onQueue('whatsapp');

        return response()->json([
            'status'  => true,
            'message' => 'Message queued successfully.',
            'data'    => [
                'id'          => $message->id,
                'phone'       => $data['phone'],
                'sales_order' => $data['sales_order'],
                'queued'      => true,
            ],
        ]);
    }

    public function templates(WhatsAppService $whatsapp)
    {
        try {
            $response = $whatsapp->getTemplates();

            // If WhatsApp API responded with error JSON
            if (isset($response['error'])) {
                return response()->json([
                    'error' => $response['error']
                ], 400);
            }

            return response()->json($response);
        } catch (\Exception $e) {
            // Catch exceptions like network errors
            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => 'Exception',
                    'code' => $e->getCode(),
                ]
            ], 500);
        }
    }


    // Removed: protected $verify_token = 'naqi_whatsapp_token'; — declared
    // but never actually referenced anywhere in this class; dead code
    // with yet another different hardcoded token value than the other
    // three occurrences that were actually in use (now all reading from
    // config('services.whatsapp.verify_token') instead).

    public function receive2(Request $request)
    {

        // ✅ Step 1: GET Verification
        if ($request->isMethod('get')) {
            $mode = $request->query('hub_mode');
            $token = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
                return response($challenge, 200);
            }

            return response('Verification token mismatch', 403);
        }

        // ✅ Step 2: POST Notification
        $entry = $request->input('entry.0.changes.0.value');

        if (!$entry || !isset($entry['messages'][0])) {
            return response()->json(['status' => 'ok']);
        }

        $message = $entry['messages'][0];

        Log::channel('whatsapp')->info('💬 Incoming WhatsApp message', [
            'from' => $message['from'] ?? null,
            'type' => $message['type'] ?? null,
            'text' => $message['button']['text'] ?? null,
        ]);

        if ($message['type'] !== 'button') {
            return response()->json(['status' => 'ok']);
        }

        $payload = $message['button']['payload'] ?? '';
        $text    = $message['button']['text'] ?? '';

        preg_match('/^(\d+)/', $payload, $matches);
        $appointmentId = $matches[1] ?? null;

        $textMap = [
            'تم' => 'confirm',
            'yes' => 'confirm',
            'Yes' => 'confirm',
            'بعدين' => 'reschedule',
            'reschedule' => 'reschedule',
            'Reschedule' => 'reschedule',
            'ماودي' => 'cancel',
            'not interested' => 'cancel',
            'Not interested' => 'cancel',
        ];

        $customerResponse = $textMap[$text] ?? null;

        if (!$appointmentId || !$customerResponse) {
            Log::channel('whatsapp')->warning('⚠️ Invalid payload or text', compact('payload', 'text'));
            return response()->json(['status' => 'ok']);
        }

        $appointment = PreAppointmentMessage::where('appointment_id', $appointmentId)
            ->latest()
            ->first();

        if (!$appointment) {
            Log::channel('whatsapp')->warning('⚠️ Appointment not found', compact('appointmentId'));
            return response()->json(['status' => 'ok']);
        }

        // تحديث رد العميل
        $appointment->update(['customer_response' => $customerResponse]);

        $requestBody = [
            '_contract' => [
                'bookId'       => $appointment->book_id,
                'salesOrderId' => $appointment->sales_order,
                'actionOwner'  => 2,
                'requestType'  => match ($customerResponse) {
                    'confirm'    => 2,
                    'cancel'     => 0,
                    'reschedule' => 1,
                },
            ],
        ];

        Log::channel('whatsapp')->info('📤 Sending request to Dy365', [
            'appointment_id' => $appointmentId,
            'request_body'   => $requestBody,
        ]);

        try {
            $response = $this->dyService->sendRequest3(
                'post',
                $this->dyService->customerChangeRequest,
                $requestBody
            );

            // ❌ Dy365 Error
            if (!$response || ($response['ok'] ?? false) === false) {

                Log::channel('whatsapp')->error('❌ Dy365 rejected request', [
                    'appointment_id' => $appointmentId,
                    'response'       => $response,
                ]);

                $appointment->update([
                    'flag'        => 0,
                    'dy_response' => $response,
                ]);
            } else {
                // ✅ Success
                Log::channel('whatsapp')->info('✅ Dy365 request success', [
                    'appointment_id' => $appointmentId,
                    'response'       => $response,
                ]);

                $appointment->update([
                    'flag'        => 1,
                    'dy_response' => $response,
                ]);
            }
        } catch (\Throwable $e) {

            Log::channel('whatsapp')->error('❌ Dy365 Exception', [
                'appointment_id' => $appointmentId,
                'error'          => $e->getMessage(),
            ]);

            $appointment->update([
                'flag'        => 0,
                'dy_response' => [
                    'exception' => $e->getMessage(),
                ],
            ]);
        }

        return response()->json(['status' => 'ok']);
    }




    public function verifyWebhook(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        if (
            $request->get('hub_mode') === 'subscribe' &&
            $request->get('hub_verify_token') === $verifyToken
        ) {
            Log::info('✅ Webhook verified');
            return response($request->get('hub_challenge'), 200);
        }

        Log::error('❌ Invalid verify token');
        return response('Invalid verify token', 403);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('📩 WhatsApp POST payload', $payload);

        if (!isset($payload['entry'][0]['changes'][0]['value'])) {
            return response()->json(['status' => 'invalid'], 400);
        }

        $value = $payload['entry'][0]['changes'][0]['value'];

        // 🟢 When user clicks a button
        if (isset($value['messages'][0]['type']) && $value['messages'][0]['type'] === 'button') {
            $message = $value['messages'][0];
            $payloadText = $message['button']['payload'] ?? '';

            Log::info("🎯 Button clicked payload: " . $payloadText);

            // Extract appointment_id + action from payload
            preg_match('/(\d+)(Yes|Reschedule|Not interested)/i', $payloadText, $matches);

            if (count($matches) === 3) {
                $appointmentId = $matches[1];
                $actionType = strtolower(str_replace(' ', '_', $matches[2]));

                Log::info("🧩 Appointment {$appointmentId} - Action: {$actionType}");

                $appointment = PreAppointmentMessage::where('appointment_id', $appointmentId)
                    ->orderByDesc('created_at')
                    ->first();

                if ($appointment) {
                    switch ($actionType) {
                        case 'yes':
                            $appointment->update(['customer_response' => 'confirm']);
                            app(DyService::class)->submitCustomerChangeRequest(
                                [
                                    "_contract" => [
                                        'bookId'        => $appointment->book_id,
                                        'actionOwner'  => 2,
                                        'salesOrderId'  => $appointment->sales_order,
                                        'requestType'   => 2, // Confirmed
                                    ]
                                ]

                            );
                            break;

                        case 'reschedule':
                            $appointment->update(['customer_response' => 'reschedule']);
                            app(DyService::class)->submitCustomerChangeRequest(
                                [
                                    "_contract" => [
                                        'bookId'        => $appointment->book_id,
                                        'salesOrderId'  => $appointment->sales_order,
                                        'actionOwner'  => 2,
                                        'requestType'   => 1, // Reschedule

                                    ]
                                ]

                            );
                            break;

                        case 'not_interested':
                            $appointment->update(['customer_response' => 'cancel']);
                            app(DyService::class)->submitCustomerChangeRequest(
                                [
                                    "_contract" => [
                                        'bookId'        => $appointment->book_id,
                                        'actionOwner'  => 2,
                                        'salesOrderId'  => $appointment->sales_order,
                                        'requestType'   => 0, // Cancel
                                    ]
                                ]

                            );
                            break;
                    }
                } else {
                    Log::warning("⚠️ No PreAppointmentMessage found for appointment {$appointmentId}");
                }
            }
        }

        // 🟠 Status updates (sent/delivered/read)
        if (isset($value['statuses'])) {
            foreach ($value['statuses'] as $status) {
                Log::info('📬 Message status update', $status);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    //https://graph.facebook.com/v17.0/109567455104882/messages

    public function sendMessage($to, $message = null)
    {

        $url = "https://graph.facebook.com/v20.0/109567455104882/messages";
        $token =  config('services.whatsapp.token', '');
        $response = Http::withToken($token)->post($url, [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'body' => $message ?? 'Hello from Laravel WhatsApp Bot!',
            ],
        ]);

        Log::info('📤 Sent message response', $response->json());

        return $response->json();
    }


    // order Lead WhatsApp message sending
    public function receive(Request $request)
    {
        // ── GET: Meta verification challenge ────────────────────────────
        if ($request->isMethod('get')) {
            $mode      = $request->query('hub_mode');
            $token     = $request->query('hub_verify_token');
            $challenge = $request->query('hub_challenge');

            if ($mode === 'subscribe' && $token === config('services.whatsapp.verify_token')) {
                return response($challenge, 200);
            }

            return response('Verification token mismatch', 403);
        }

        // ── POST: inbound message ────────────────────────────────────────
        $entry = $request->input('entry.0.changes.0.value');

        // Delivery status update (sent/delivered/read/failed). This was
        // previously completely missing from this method — any status
        // callback was silently discarded here, meaning "accepted" (from
        // the original send response) was the ONLY status this app ever
        // recorded, regardless of what actually happened afterward.
        if ($entry && isset($entry['statuses'][0])) {
            foreach ($entry['statuses'] as $statusEvent) {
                $this->applyDeliveryStatus($statusEvent);
            }

            return response()->json(['status' => 'ok']);
        }

        if (! $entry || ! isset($entry['messages'][0])) {
            return response()->json(['status' => 'ok']);
        }

        $message = $entry['messages'][0];

        if ($message['type'] !== 'button') {
            return response()->json(['status' => 'ok']);
        }

        // ── Route by context.id → lead message IDs (most reliable) ──────
        $contextId = $message['context']['id'] ?? null;

        if ($contextId) {

            $lead = OrderLead::where('q1_message_id', $contextId)
                ->orWhere('q2_message_id', $contextId)
                ->first();


            if ($lead) {
                return $this->handleLeadQuestion($message, $lead, $contextId);
            }
        }

        // ── SCENARIO A: Appointment confirmation (existing — untouched) ──
        $payload = $message['button']['payload'] ?? '';
        $text    = $message['button']['text']    ?? '';

        preg_match('/^(\d+)/', $payload, $matches);
        $appointmentId = $matches[1] ?? null;

        $textMap = [
            'تم'             => 'confirm',
            'yes'            => 'confirm',
            'Yes'            => 'confirm',
            'بعدين'          => 'reschedule',
            'reschedule'     => 'reschedule',
            'Reschedule'     => 'reschedule',
            'ماودي'          => 'cancel',
            'not interested' => 'cancel',
            'Not interested' => 'cancel',
        ];

        $customerResponse = $textMap[$text] ?? null;

        if (! $appointmentId || ! $customerResponse) {
            return response()->json(['status' => 'ok']);
        }

        $appointment = PreAppointmentMessage::where('appointment_id', $appointmentId)
            ->latest()
            ->first();

        if (! $appointment) {
            return response()->json(['status' => 'ok']);
        }

        $appointment->update(['customer_response' => $customerResponse]);

        $requestBody = [
            '_contract' => [
                'bookId'       => $appointment->book_id,
                'salesOrderId' => $appointment->sales_order,
                'actionOwner'  => 2,
                'requestType'  => match ($customerResponse) {
                    'confirm'    => 2,
                    'cancel'     => 0,
                    'reschedule' => 1,
                },
            ],
        ];



        try {
            $response = $this->dyService->sendRequest3(
                'post',
                $this->dyService->customerChangeRequest,
                $requestBody
            );

            if (! $response || ($response['ok'] ?? false) === false) {
                Log::channel('whatsapp')->error('❌ Dy365 rejected request', [
                    'appointment_id' => $appointmentId,
                    'response'       => $response,
                ]);
                $appointment->update(['flag' => 0, 'dy_response' => $response]);
            } else {
                Log::channel('whatsapp')->info('✅ Dy365 request success', [
                    'appointment_id' => $appointmentId,
                    'response'       => $response,
                ]);
                $appointment->update(['flag' => 1, 'dy_response' => $response]);
            }
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('❌ Dy365 Exception', [
                'appointment_id' => $appointmentId,
                'error'          => $e->getMessage(),
            ]);
            $appointment->update([
                'flag'        => 0,
                'dy_response' => ['exception' => $e->getMessage()],
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    // ────────────────────────────────────────────────────────────────────
    // SCENARIO B — Lead scoring Q1 / Q2
    // ────────────────────────────────────────────────────────────────────

    /**
     * Handle a lead scoring button reply.
     *
     * Payload format (new): "{orderNumber}#{choiceId}#{choiceText}"
     * e.g. "ORD-20260624-00002#Q1A#جاهز للحجز والتركيب"
     *
     * We parse:
     *   - choiceId   → e.g. Q1A, Q1B, Q2C  (used to look up score reliably)
     *   - choiceText → human-readable label
     *
     * The lead is already resolved via context.id before this is called.
     */
    private function handleLeadQuestion(array $message, OrderLead $lead, string $contextId): \Illuminate\Http\JsonResponse
    {


        try {
            $payload    = $message['button']['payload'] ?? '';
            $choiceText = $message['button']['text']    ?? '';

            // Parse the new payload format: orderNumber#choiceId#choiceText
            // Falls back gracefully if old format is received
            $parts    = explode('#', $payload);
            $choiceId = $parts[1] ?? null; // e.g. Q1A, Q1B, Q2C, Q2D


            // ── Q1 answer ─────────────────────────────────────────────
            if ($lead->isQ1Sent()) {

                // Special case: choosing option B on Q1 means "just an
                // inquiry" — acknowledge with a fixed message and stop
                // here entirely. Do NOT save a scoreable response, do NOT
                // send Q2, do NOT run scoring for this lead.
                if ($choiceId === 'Q1B' || $choiceText === 'B') {
                    $this->whatsAppService->sendTextMessage(
                        $lead->mobile_number,
                        "مرحباً بك في *نقي* 👋\n"
                            . "تم استلام طلبكم بـ الاستفسار، وسيقوم فريقنا بخدمتك والرد عليك خلال ساعات العمل.\n"
                            . "🕘 *ساعات العمل:* من [08:00] صباحاً إلى [08:00] مساءاً\n"
                            . "من [السبت] إلى [الخميس]\n"
                            . "نسعد بخدمتك."
                    );

                    $this->leadService->updateStatus($lead, 'inquiry');

                    return response()->json(['status' => 'ok']);
                }

                // Save response — ResponseService resolves score from choice text or ID
                $this->responseService->saveResponse($lead->id, 1, $choiceId ?? $choiceText);

                $this->leadService->updateStatus($lead, 'q1_answered');

                // Send Q2
                $q2 = $this->whatsAppService->sendQuestion2(
                    $lead->mobile_number,
                    $lead->order_number,
                    $lead->product       ?? 'استفسارك'
                );

                $q2Response = $q2->json();
                // TelegramService::send(
                //     "📩 isQ1Sent q2Response \n\n" .
                //         json_encode($q2Response, JSON_PRETTY_PRINT)
                // );
                if (
                    isset($q2Response['messages'][0]['message_status']) &&
                    $q2Response['messages'][0]['message_status'] === 'accepted'
                ) {
                    $q2MessageId = $this->leadService->extractMessageId($q2Response);
                    if ($q2MessageId) {
                        $this->leadService->saveMessageId($lead, 2, $q2MessageId);
                    }
                    $this->leadService->updateStatus($lead, 'q2_sent');
                }


                return response()->json(['status' => 'ok']);
            }


            if ($lead->isQ1Answered()) {

                $q2 = $this->whatsAppService->sendQuestion2(
                    $lead->mobile_number,
                    $lead->order_number,
                    $lead->product       ?? 'استفسارك'
                );

                $q2Response = $q2->json();
                // TelegramService::send(
                //     "📩 isQ1Sent q2Response \n\n" .
                //         json_encode($q2Response, JSON_PRETTY_PRINT)
                // );
                if (
                    isset($q2Response['messages'][0]['message_status']) &&
                    $q2Response['messages'][0]['message_status'] === 'accepted'
                ) {
                    $q2MessageId = $this->leadService->extractMessageId($q2Response);
                    if ($q2MessageId) {
                        $this->leadService->saveMessageId($lead, 2, $q2MessageId);
                    }
                    $this->leadService->updateStatus($lead, 'q2_sent');
                }
                return response()->json(['status' => 'ok']);
            }
            // ── Q2 answer ─────────────────────────────────────────────
            if ($lead->isQ2Sent()) {

                $this->responseService->saveResponse($lead->id, 2, $choiceId ?? $choiceText);
                $this->leadService->updateStatus($lead, 'q2_answered');

                $result = $this->scoringService->calculateAndSave($lead->id);
                // $this->notificationService->notifySalesTeam($result['lead'], $result);

                return response()->json(['status' => 'ok']);
            }



            return response()->json(['status' => 'ok']);
        } catch (\InvalidArgumentException $e) {
            Log::channel('whatsapp')->warning('⚠️ Invalid lead answer', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('❌ Lead question handler error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'ok']);
        }
    }

    /**
     * Persists a delivery status callback (sent/delivered/read/failed)
     * against whichever record actually owns that message_id — checks
     * PreAppointmentMessage first, then OrderLead's q1/q2 slots.
     *
     * This is the piece that was entirely missing before: without this,
     * "is_sent"/"accepted" was permanently the only status ever recorded,
     * regardless of what actually happened to the message afterward.
     */
    protected function applyDeliveryStatus(array $statusEvent): void
    {
        $messageId = $statusEvent['id'] ?? null;
        $status    = $statusEvent['status'] ?? null; // sent, delivered, read, failed

        if (!$messageId || !$status) {
            return;
        }

        $errorMessage = null;
        if ($status === 'failed' && !empty($statusEvent['errors'][0])) {
            $error = $statusEvent['errors'][0];
            $errorMessage = trim(
                ($error['title'] ?? '') . ' - ' . ($error['message'] ?? '') . ' (code ' . ($error['code'] ?? '?') . ')'
            );
        }

        Log::channel('whatsapp')->info('📬 Delivery status update', [
            'message_id' => $messageId,
            'status'     => $status,
            'error'      => $errorMessage,
        ]);

        $preAppointmentMessage = PreAppointmentMessage::where('message_id', $messageId)->first();

        if ($preAppointmentMessage) {
            $preAppointmentMessage->update([
                'delivery_status' => $status,
                'delivery_error'  => $errorMessage,
            ]);
            return;
        }

        $lead = OrderLead::where('q1_message_id', $messageId)->first();

        if ($lead) {
            $lead->update([
                'q1_status' => $status,
                'q1_error'  => $errorMessage,
            ]);
            return;
        }

        $lead = OrderLead::where('q2_message_id', $messageId)->first();

        if ($lead) {
            $lead->update([
                'q2_status' => $status,
                'q2_error'  => $errorMessage,
            ]);
            return;
        }

        Log::channel('whatsapp')->warning('📬 Delivery status for unknown message_id', [
            'message_id' => $messageId,
            'status'     => $status,
        ]);
    }
}
