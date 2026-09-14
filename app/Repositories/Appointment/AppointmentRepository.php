<?php

namespace App\Repositories\Appointment;

use App\Models\Item;
use App\Models\Part;
use App\Models\User;
use App\Models\Invoice;
use App\Helper\FcmHelper;
use App\Models\UserStock;
use App\Models\Appointment;
use App\Models\InvoiceItem;
use App\Models\CompleteNote;
use App\Models\CompleteImage;
use App\Models\AppointmentItem;
use App\Models\AppointmentLine;
use App\Models\RescheduleImage;
use App\Models\PeriodicMainItem;
use App\Models\RescheduleReason;
use App\Helper\ApiResponseHelper;
use App\Models\CancellationImage;
use App\Models\EmergencyItemPart;
use App\Models\EmergencyMainItem;
use App\Services\DY365\DyService;
use Illuminate\Http\UploadedFile;
use App\Models\AppointmentPayment;
use App\Models\CancellationReason;
use Illuminate\Support\Facades\DB;
use App\Services\TaqnyatSmsService;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncTechnicianStockJob;
use App\Models\PeriodicMainItemPart;
use Illuminate\Support\Facades\Auth;
use App\Models\EmergencyMainItemPart;
use App\Services\Payment\TabbyService;
use App\Notifications\TechNotification;
use App\Services\Payment\TamaraService;
use Illuminate\Support\Facades\Storage;
use App\Jobs\CompleteSuccessPaymentsJob;
use App\Repositories\Tech\TechRepository;
use App\Services\Payment\ClickPayService;
use App\Services\WarehouseTransferFillService;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Appointment\GetAllAppointmentsResource;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;
use Mockery\Matcher\Type;

class AppointmentRepository implements AppointmentRepositoryInterface
{

    use ApiResponseHelper;

    protected $warehouseTransferFillService;
    protected $techRepository;
    protected $smsService;
    protected  $dyService;



    public function __construct(DyService $dy_service, WarehouseTransferFillService $warehouseTransferFillService, TechRepository $techRepository, TaqnyatSmsService $smsService)
    {
        $this->dyService = $dy_service;
        $this->warehouseTransferFillService = $warehouseTransferFillService;
        $this->techRepository = $techRepository;
        $this->smsService = $smsService;
    }


    public function all($perPage, $page, $request)
    {
        $authUser = Auth::user();

        if (!$authUser->hasPermissionTo('view appointments')) {
            return $this->setCode(401)
                ->setData([])
                ->setMessage('You are not authorized to view this appointment.')
                ->send();
        }


        $perPage = $request->input('per_page', 10);
        $search = $request->input('q');
        $query = Appointment::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_status')) {
            $query->where('billing_status', $request->billing_status);
        }

        // ✅ Filter by appointment_date range (convert to Y-m-d)
        if ($request->filled('appointment_date_from') && $request->filled('appointment_date_to')) {
            try {
                $from = \Carbon\Carbon::parse($request->appointment_date_from)->format('Y-m-d');
                $to   = \Carbon\Carbon::parse($request->appointment_date_to)->format('Y-m-d');
                $query->whereDate('appointment_date', '>=', $from)
                    ->whereDate('appointment_date', '<=', $to);
            } catch (\Exception $e) {
                return $this->setCode(400)
                    ->setData([])
                    ->setMessage('Invalid date format. Please use a valid date.')
                    ->send();
            }
        }

        // ✅ Single appointment_date filter
        if ($request->filled('appointment_date')) {
            try {
                $date = \Carbon\Carbon::parse($request->appointment_date)->format('Y-m-d');
                $query->whereDate('appointment_date', $date);
            } catch (\Exception $e) {
                return $this->setCode(400)
                    ->setData([])
                    ->setMessage('Invalid appointment_date format. Please use a valid date.')
                    ->send();
            }
        }

        // ✅ New Filter: Sales Order ID
        if ($request->filled('sales_order_id')) {
            $query->where('sales_order_id', $request->sales_order_id);
        }

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('power_socket')) {
            $query->where('power_socket', $request->power_socket);
        }



        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_num', 'like', "%$search%")
                    ->orWhere('whats_app', 'like', "%$search%")
                    ->orWhere('id', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");


                // Search in customer name

                $q->orWhereHas('customer', function ($query) use ($search) {
                    $query->where('username', 'like', "%$search%");
                });

                // Search in technician name
                $q->orWhereHas('technician', function ($query) use ($search) {
                    $query->where('username', 'like', "%$search%");
                });
            });
        }
        $appointments = $query->paginate($perPage);

        // statistics of appointments
        $baseStatuses = ['processing', 'complete', 'completed'];
        $totalAppointments = Appointment::whereIn('status', $baseStatuses)->count();
        $totalNotCompleted = Appointment::whereIn('status', $baseStatuses)
            ->where(function ($q) {
                $q->whereNull('dy365_status')
                    ->orWhere('dy365_status', '!=', 'Completed');
            })
            ->count();

        $totalCompleted = Appointment::whereIn('status', $baseStatuses)
            ->where('dy365_status', 'Completed')
            ->count();

        return $this->setCode(200)
            ->setData([
                'appointments' => GetAllAppointmentsResource::collection($appointments->items()),
                'pagination' => [
                    'current_page' => $appointments->currentPage(),
                    'total_pages' => $appointments->lastPage(),
                    'per_page' => $appointments->perPage(),
                    'total_items' => $appointments->total(),
                ],
                'counts' => [
                    'total_processing_complete' => $totalAppointments,
                    'total_not_completed_dy365' => $totalNotCompleted,
                    'total_completed_dy365' => $totalCompleted,
                ],
            ])
            ->setMessage('success')
            ->send();
    }


    public function find($id)
    {
        $authUser = Auth::user();
        // Check if the user is the assigned technician OR has a specific permission
        $appointment = Appointment::with('complete.images')->findOrFail($id);
        if (
            $appointment->technician_id !== $authUser->id &&
            !$authUser->hasPermissionTo('view appointments')
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to view this appointment.')->send();
        }
        $appointment = $appointment->load(['customer', 'lines', 'changeStatusRequests']);
        $user = $appointment->technician;
        if (!$user) {
            return $this->setCode(code: 404)->setData([])->setMessage('Technician not found.')->send();
        }
        $missing = $this->techRepository->newMissingInventory($user, $appointment);
        $hasMissing = (
            (!empty($missing['missing_items']) && count($missing['missing_items']) > 0) ||
            (!empty($missing['missing_parts']) && count($missing['missing_parts']) > 0)
        );

        return $this->setCode(code: 200)->setData(["appointment" => new AppointmentResource($appointment, $missing, $hasMissing)])->setMessage('Success.')->send();
    }

    public function create(array $data)
    {
        $authUser = Auth::user();
        if (
            !$authUser->hasPermissionTo('create appointments') // or any permission name
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to create  appointment.')->send();
        }

        // Create appointment
        $appointment = Appointment::create($data);

        //
        $warehouseData['requested_items'] = $data['items'] ?? [];
        $warehouseData['requested_parts'] = $data['parts'] ?? [];

        $userStock = $authUser->stock
            ?? (isset($data['technician_id']) ? User::find($data['technician_id'])?->stock : null)
            ?? $appointment->technician?->stock;

        if (!$userStock) {
            return $this->setCode(code: 401)->setData([])->setMessage('No stock found for the tech.')->send();
        }

        $this->warehouseTransferFillService->validateItemsAndParts($warehouseData, "tech_to_warehouse", 1, $userStock);


        // Handle appointment items && parts
        $subTotalItems = 0;
        if (isset($data['items']) && is_array($data['items'])) {
            $subTotalItems = $this->createAppointmentComponents($appointment, $data);
        }

        $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
        $discountType = $data['discount_type'] ?? 'fixed';
        if ($discountType === 'percentage') {
            $discount = $subTotalItems * ($discount / 100);
        }
        $data['total_price'] = $subTotalItems - $discount;
        $data['collect'] = $data['collect'] ?? 0;
        $paid = isset($data['paid']) ? floatval($data['paid']) : 0;
        $data['paid'] = $paid; // Ensure it's saved
        $data['collect'] = $data['total_price'] - $paid;
        // Update appointment sub_total_price and total_price
        $appointment->update([
            'sub_total_price' =>   $subTotalItems,
            'discount' =>  $data['discount'] ?? 0,
            'discount_type' =>  $discountType,
            'discount_value' => $data['discount'] ?? 0,
            'total_price' => $data['total_price'],
            'paid' => $paid,
            'status' => $data['status'] ?? "pending",
            'collect' => $data['collect'],
        ]);
        //create invoice
        $invoice = $this->createInvoice($appointment);
        return $this->setCode(code: 200)->setData(new AppointmentResource($appointment))->setMessage('Success.')->send();
    }

    // update appointment status
    public function update($id, array $data)
    {
        $appointment = Appointment::findOrFail($id);
        if (!$appointment) {
            return $this->setCode(code: 404)->setData([])->setMessage('Appointment not found.')->send();
        }
        $tech = $appointment->technician;
        if (!$tech) {
            return $this->setCode(code: 404)->setData([])->setMessage('Technician not found.')->send();
        }
        // return $data;
        // Check if the user is the assigned technician OR has a specific permission
        $authUser = Auth::user();
        if (
            $appointment->technician_id !== $authUser->id &&
            !$authUser->hasPermissionTo('update appointments') // or any permission name
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to update this appointment.')->send();
        }
        $this->updateAppointmentDetailsIfPresent($appointment, $data);

        if (isset($data['items']) || isset($data['parts'])) {
            if (
                $appointment->status == "complete"
            ) {
                return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to update this appointment.')->send();
            }
            $warehouseData['requested_items'] = $data['items'] ?? [];
            $warehouseData['requested_parts'] = $data['parts'] ?? [];

            $userStock = $authUser->stock
                ?? (isset($data['technician_id']) ? User::find($data['technician_id'])?->stock : null)
                ?? $appointment->technician?->stock;

            if (!$userStock) {
                return $this->setCode(code: 401)->setData([])->setMessage('No stock found for the tech.')->send();
            }

            $this->warehouseTransferFillService->validateItemsAndParts($warehouseData, "tech_to_warehouse", 1, $userStock);

            if (isset($data['items']) && is_array($data['items'])) {
                $appointment->items()->delete(); // Remove existing items before updating
                $subTotalItems = $this->createAppointmentComponents($appointment, $data);
            }
            if (!isset($data['items']) && isset($data['parts']) && is_array($data['parts'])) {
                // Delete parts from appropriate source
                if ($appointment->type === 'emergency') {
                    foreach ($appointment->emergencyItems as $emergencyItem) {
                        $emergencyItem->parts()->delete();
                    }
                } elseif ($appointment->type === 'periodic') {
                    foreach ($appointment->periodicItems as $periodicItem) {
                        $periodicItem->parts()->delete();
                    }
                } else {
                    $appointment->parts()->delete(); // fallback for installation
                }

                $subTotalItems = $this->createAppointmentComponents($appointment, $data);
            }

            $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
            $discountType = $data['discount_type'] ?? 'fixed';
            if ($discountType === 'percentage') {
                $discount = $subTotalItems * ($discount / 100);
            }

            $data['total_price'] = $subTotalItems - $discount;
            // $data['collect'] = $data['collect'] ?? 0;
            $paid = isset($data['paid']) ? floatval($data['paid']) : 0;
            $data['paid'] = $paid; // Ensure it's saved
            $data['collect'] =    $subTotalItems -   $paid;
            // dd($subTotalItems, $data['collect'], $data['total_price'], $paid);
            // update appointment sub_total_price and total_price
            $appointment->update([
                'sub_total_price' =>   $subTotalItems,
                'discount' =>  $data['discount'] ?? 0,
                'discount_type' => $discountType,
                'discount_value' => $data['discount'] ?? 0,
                'total_price' => $data['total_price'],
                'paid' => $paid,
                'status' => $data['status'] ?? "pending",
                'collect' => $data['collect'],
            ]);
        }

        // cancel or  reschedule
        if (isset($data['status']) && in_array($data['status'], ['reschedule', 'cancel'])) {
            $appointment->update(['status' => $data['status']]);

            if ($data['status'] === 'reschedule') {
                if ($tech?->id) {
                    $this->dyService->changeAppointmentStatus([
                        'worker' => $tech->tech_id,
                        'salesOrderId' =>  $appointment->sales_order_id,
                        'requestType' => 1,
                    ]);
                }

                $appointment->reschedule_notes = $data['reschedule_notes'] ?? null;

                $rescheduleReason = RescheduleReason::create([
                    'appointment_id' => $appointment->id,
                    'reason' => $data['reason'] ?? null,
                ]);

                if (isset($data['images'])) {
                    foreach ($data['images'] as $image) {
                        $extension = $image->getClientOriginalExtension();
                        $fileName = uniqid('reschedule', true) . '.' . $extension;
                        $imagePath = $image->storeAs('reschedule/' . $appointment->id, $fileName, 's3');

                        RescheduleImage::create([
                            'reschedule_reason_id' => $rescheduleReason->id,
                            'image' => $imagePath,
                        ]);
                    }
                }
            }

            if ($data['status'] === 'cancel') {
                $payload = [
                    'worker'        => $tech->tech_id,
                    'salesOrderId'  => $appointment->sales_order_id,
                    'requestType'   => 0,
                ];

                $this->dyService->changeAppointmentStatus($payload);
                $appointment->cancel_notes = $data['cancel_notes'] ?? null;

                $cancellationReason = CancellationReason::create([
                    'appointment_id' => $appointment->id,
                    'reason' => $data['reason'] ?? null,
                ]);

                if (isset($data['images'])) {
                    foreach ($data['images'] as $image) {
                        $extension = $image->getClientOriginalExtension();
                        $fileName = uniqid('cancellation', true) . '.' . $extension;
                        $imagePath = $image->storeAs('cancellation/' . $appointment->id, $fileName, 's3');

                        CancellationImage::create([
                            'cancellation_reason_id' => $cancellationReason->id,
                            'image' => $imagePath,
                        ]);
                    }
                }
            }

            $appointment->save(); // Save notes if needed
        }


        if (isset($data['status']) && $data['status'] === 'hold') {
            $appointment->update(['status' => "hold"]);
            $appointment->hold_reason = $data['reason'];
        }
        if (isset($data['status']) && $data['status'] === 'on_way') {
            $appointment->update(['status' => "on_way"]);
        }
        if (isset($data['status']) && $data['status'] === 'on_site') {
            $appointment->update(['status' => "on_site"]);
        }


        return $this->setCode(code: 200)->setData(new AppointmentResource($appointment))->setMessage('Success.')->send();
    }

    // update appointment attributes
    private function updateAppointmentDetailsIfPresent(Appointment $appointment, array $data): void
    {

        $fieldsToUpdate = [
            'customer_id',
            'address_id',
            'whats_app',
            'phone',
            'appointment_num',
            'technician_notes',
            'technician_id',
            'type',
            'address',
            'latitude',
            'longitude',
            'branch',
            'sector',
            'appointment_date',
            'installation_date',
            'appointment_time',
            'power_socket',
            'service_type',
            'status',
        ];

        $updates = [];

        foreach ($fieldsToUpdate as $field) {
            if (array_key_exists($field, $data)) {
                $updates[$field] = $data[$field];
            }
        }

        if (!empty($updates)) {
            $appointment->update($updates);
        }
    }


    public function delete(Appointment $appointment)
    {
        return $appointment->delete();
    }

    // updateStatus method  to on  way,on site
    public function updateStatus(int $id, string $status): Appointment
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->status = $status;
        $appointment->save();

        return $appointment;
    }


    public function getTechnicianAppointments(int $technicianId)
    {
        return Appointment::where('technician_id', $technicianId)->get();
    }

    // completeAppointment method
    public function completeAppointment(Appointment $appointment, array $data)
    {
        $authUser = Auth::user();
        if (
            $appointment->technician_id !== $authUser->id &&
            !$authUser->hasPermissionTo('update appointments') // or any permission name
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to view this appointment.')->send();
        }

        // Update appointment status to complete
        $appointment->update(['status' => 'complete']);

        if (isset($data['lines']) && is_array($data['lines'])) {
            foreach ($data['lines'] as $itemData) {
                if (!isset($itemData['line_id'])) {
                    continue; // Skip if no line ID
                }
                $appointmentLines = $appointment->lines()->where('id', $itemData['line_id'])->first();
                if ($appointmentLines) {
                    $appointmentLines->update([
                        'check_list' => $itemData['check_list'],
                        'floor' => $itemData['floor'],
                        'apart' => $itemData['apart'],
                        'room' => $itemData['room'],
                    ]);
                }
            }
        }
        // complete note && images
        if (isset($data['note'])) {
            $completeNote = CompleteNote::create([
                'appointment_id' => $appointment->id,
                'note' => $data['note']
            ]);
            $attachmentUrls = []; // collect S3 image URLs
            if (!empty($data['images']) && is_array($data['images'])) {
                $attachmentUrls = [];

                foreach ($data['images'] as $image) {
                    if (!$image instanceof \Illuminate\Http\UploadedFile) {
                        Log::warning("Invalid image type for appointment {$appointment->id}");
                        continue;
                    }

                    $extension = $image->getClientOriginalExtension();
                    $fileName = uniqid('complete_', true) . '.' . $extension;
                    $folder = "complete/{$appointment->id}";
                    $imagePath = $image->storeAs($folder, $fileName, 's3');

                    // Save record to DB
                    $completeImage = CompleteImage::create([
                        'complete_note_id' => $completeNote->id,
                        'image' => $imagePath,
                    ]);

                    // Get temporary signed URL (using your helper)
                    $temporaryUrl = $this->getImageUrl($imagePath);

                    // Add to payload only if URL generated
                    if ($temporaryUrl) {
                        $attachmentUrls[] = $temporaryUrl;
                    } else {
                        Log::warning("Failed to generate image URL for {$imagePath}");
                    }
                }
            }

            // ✅ Call completeAppointmentWithAttachments after images are uploaded
            $payload = [
                "_contract" => [
                    "BookId" => $appointment->book_id,
                    "Note" => $data['note'],
                    "AttachmnetsURLs" => $attachmentUrls,
                ]
            ];
            // Call your function safely
            try {
                $this->dyService->completeAppointmentWithAttachments($payload);
            } catch (\Exception $e) {
                Log::error("Failed to call completeAppointmentWithAttachments for appointment {$appointment->id}: " . $e->getMessage());
            }
        }
        $invoice = $this->createInvoice($appointment);
        // $this->deductAppointmentFromTechStock($appointment);
        $this->deductAppointmentLinesFromWarehouse($appointment);

        try {
            $user = $appointment->technician;
            if ($user) {
                SyncTechnicianStockJob::dispatch($user->tech_id);
            }
        } catch (\Exception $e) {
            Log::error("Failed to dispatch technician stock job for user {$user->id}: " . $e->getMessage());
            // continue without breaking
        }

        $appointment = $appointment->load(['invoices', 'payments', 'lines']);
        return $this->setCode(code: 200)->setData(new AppointmentResource($appointment))->setMessage('Success.')->send();
    }
    // s3 images :
    private function getImageUrl($path)
    {
        if ($path && Storage::disk('s3')->exists($path)) {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(100));
        }
        return null;
    }
    // new appointmentPaymentStore method

    public function appointmentPaymentStore($id, array $data)
    {
        // return $data;
        $authUser    = Auth::user();
        $appointment = Appointment::with('payments')->findOrFail($id);
        $tech        = $appointment->technician;

        if (!$tech) {
            return $this->setCode(404)->setData([])->setMessage('Technician not found.')->send();
        }

        if ($appointment->technician_id !== $authUser->id && !$authUser->hasPermissionTo('create payments')) {
            return $this->setCode(401)->setData([])->setMessage('You are not authorized to view this appointment.')->send();
        }

        $results   = [];
        $payments  = collect($data['payments']);
        $is_single  = $payments->count() === 1;
        $is_two     = $payments->count() === 2;
        // $totalDue  = $appointment->total_price; // <-- assumes you have this column

        foreach ($payments as $payment) {
            $paymentType   = strtolower($payment['payment_type'] ?? '');
            $phone         = $payment['phone'] ?? $appointment->customer?->phone ?? $appointment->phone;
            $paymentAmount = floatval($payment['total_price'] ?? 0);
            $paymentStatus = 'created';

            // ✅ CASE 1: Single Payment
            if ($is_single) {
                if ($paymentType === 'cash' || $paymentType === 'pos') {
                    // if full payment
                    $appointment->collect  = 0;
                    $appointment->paid    = $appointment->total_price;
                    $appointment->billing_status = 'paid';
                    $appointment->save();

                    if ($paymentType === 'pos') {
                        $body = [
                            "_contract" => [
                                "worker" => $tech->tech_id,
                                "SalesOrderId" => $appointment->sales_order_id,
                                "BookId" => $appointment->book_id,
                                "Discount" => $appointment->discount_value ?? 0,
                                "salesLines" => [
                                    [
                                        "PaymentReference" => $payment->payment_id
                                            ?: ($payment['reference_id'] ?? null),
                                        "TotalAmount" => $paymentAmount,
                                        "PaymentMethod" => "POS"
                                    ]
                                    // want to send cash payment if exists
                                ]
                            ]
                        ];
                    } elseif ($paymentType === 'cash') {
                        // DY Complete
                        $body = [
                            "_contract" => [
                                "worker" => $tech->tech_id,
                                "SalesOrderId" => $appointment->sales_order_id,
                                "BookId" => $appointment->book_id,
                                "Discount" => $appointment->discount_value ?? 0,
                                "salesLines" => [
                                    [
                                        'PaymentReference' => $payment->payment_id
                                            ?: ($payment->reference_id ?: null),


                                        "TotalAmount" => $paymentAmount,
                                        "PaymentMethod" => "CASH"
                                    ]
                                    // want to send cash payment if exists
                                ]
                            ]
                        ];
                    }
                    $appointment->update([
                        'status' => 'processing'
                    ]);
                    $appointment->save();
                    // CompleteSuccessPaymentsJob::dispatch($appointment, $body);
                    $cmd = "php " . escapeshellarg(base_path('artisan')) . " payments:complete "
                        . escapeshellarg($appointment->id) . " "
                        . escapeshellarg(json_encode($body))
                        . " > /dev/null 2>&1 &";

                    exec($cmd);

                    $paymentStatus = 'Success';
                } elseif (in_array($paymentType, ['tabby', 'tamara', 'clickpay'])) {

                    // ✅ If payment already has a reference ID, skip link creation and save directly
                    if (!empty($payment['reference_id'])) {
                        $appointment->payments()->create([
                            'payment_type'         => $paymentType,
                            'payment_reference_id' => $payment['reference_id'],
                            'phone_number'         => $phone,
                            'total_price'          => $paymentAmount,
                            'status'               => 'Success',
                        ]);

                        $appointment->update([
                            'billing_status' => 'paid',
                            'status'         => 'processing',
                            'paid'           => $appointment->total_price,
                            'collect'        => 0,
                        ]);

                        $appointment->save();
                    } else {
                        // Checkout creation normally
                        if ($paymentType === 'tabby') {
                            $tabbyResponse = app(TabbyService::class)->checkout($appointment, $paymentAmount, $phone, $is_single);
                            $tabbyData = $tabbyResponse->getData(true);

                            if ((!isset($tabbyData['web_url'])) || (isset($tabbyData['error']))) {
                                if (isset($tabbyData['details']['configuration']['products']['installments']['rejection_reason'])) {
                                    $tabbyUrl = $tabbyData['details']['configuration']['products']['installments']['rejection_reason'];
                                    $isError = true;
                                } else {
                                    $tabbyUrl = (string) json_encode($tabbyData, JSON_UNESCAPED_UNICODE);
                                    $isError = true;
                                }
                            } else {
                                $tabbyUrl = $tabbyData['web_url'] ?? (string) $tabbyData;
                                $isError = false;
                            }

                            if ($tabbyUrl) {
                                $results['tabby_url'] = $tabbyUrl;
                                if (!$isError) {
                                    $this->smsService->sendPaymentLink($phone, $tabbyUrl);
                                } else {
                                    \Log::warning('Tabby returned error, not sending link', ['response' => $tabbyData]);
                                }
                            } else {
                                \Log::warning('Tabby checkout did not return web_url', $tabbyData);
                            }
                        } elseif ($paymentType === 'tamara') {
                            app(TamaraService::class)->pre_checkout($phone, $paymentAmount);
                            $tamara = app(TamaraService::class)->createOrder($appointment, $paymentAmount, $phone, $is_single);
                            $results['tamara_url'] = $tamara['checkout_url'] ?? null;
                            $this->smsService->sendPaymentLink($phone, $results['tamara_url']);
                        } elseif ($paymentType === 'clickpay') {
                            $clickpay = app(ClickPayService::class)->createInvoice($appointment, $paymentAmount, $phone, $is_single);
                            $results['clickpay_url'] = $clickpay['redirect_url'] ?? null;
                            $this->smsService->sendPaymentLink($phone, $results['clickpay_url']);
                        }
                    }
                }


                $appointment->payments()->create([
                    'payment_type'         => $paymentType,
                    'payment_reference_id' => $payment['reference_id'] ?? null,
                    'phone_number'         => $phone,
                    'total_price'          => $paymentAmount,
                    'status'               => in_array(strtolower($paymentType), ['cash', 'pos'])
                        ? 'Success'
                        : $paymentStatus,
                ]);
            }
            // ✅ CASE 2: Multi-Payments
            else {
                //check if payment type is valid
                $types = $payments->pluck('payment_type')->map(fn($t) => strtolower($t))->toArray();
                // Must be exactly 2
                if (count($types) !== 2) {
                    return $this->setCode(422)
                        ->setData([])
                        ->setMessage('Only two payments allowed when splitting.')
                        ->send();
                }
                $hasCash = in_array('cash', $types);
                $hasPos  = in_array('pos', $types);
                $hasGateway = count(array_intersect($types, ['tabby', 'tamara', 'clickpay'])) > 0;
                // ❌ Invalid: two gateways without cash/pos
                if ($hasGateway && !($hasCash || $hasPos)) {
                    return $this->setCode(422)
                        ->setData([])
                        ->setMessage('You must combine Tabby/Tamara/ClickPay with CASH or POS.')
                        ->send();
                }
                // end checking payment types

                if (in_array($paymentType, ['cash', 'pos'])) {
                    if ($appointment->collect > 0) {
                        $appointment->collect = max(0, $appointment->collect - $paymentAmount);
                    }

                    $appointment->paid += $paymentAmount;
                    $appointment->save();
                } elseif (in_array($paymentType, ['tabby', 'tamara', 'clickpay'])) {
                    if ($paymentType === 'tabby') {
                        $tabbyResponse = app(TabbyService::class)->checkout($appointment, $paymentAmount, $phone, $is_single);
                        $tabbyData = $tabbyResponse->getData(true);
                        if ((!isset($tabbyData['web_url'])) || (isset($tabbyData['error']))) {
                            $tabbyUrl = (string)$tabbyData['details']['rejection_reason_code'] ?? "Failed to create Tabby session.";
                        } else {
                            $tabbyUrl = $tabbyData['web_url'] ?? (string) $tabbyData;
                        }
                        if ($tabbyUrl) {
                            $results['tabby_url'] = $tabbyUrl;
                            $this->smsService->sendPaymentLink($phone, $tabbyUrl);
                        } else {
                            \Log::warning('Tabby checkout did not return web_url', $tabbyData);
                        }
                        // $results['tabby_url'] = $tabbyData['web_url'] ?? null;
                        // $this->smsService->sendPaymentLink($phone, $results['tabby_url']);
                    } elseif ($paymentType === 'tamara') {
                        app(TamaraService::class)->pre_checkout($phone, $paymentAmount);
                        $tamara = app(TamaraService::class)->createOrder($appointment, $paymentAmount, $phone, $is_single);
                        $results['tamara_url'] = $tamara['checkout_url'] ?? null;
                        $this->smsService->sendPaymentLink($phone, $results['tamara_url']);
                    } elseif ($paymentType === 'clickpay') {
                        $clickpay = app(ClickPayService::class)->createInvoice($appointment, $paymentAmount, $phone, $is_single);
                        $results['clickpay_url'] = $clickpay['redirect_url'] ?? null;
                        $this->smsService->sendPaymentLink($phone, $results['clickpay_url']);
                    }
                }
            }
            // ✅ Save each payment row
            $appointment->payments()->create([
                'payment_type'         => $paymentType,
                'payment_reference_id' => $payment['reference_id'] ?? null,
                'phone_number'         => $phone,
                'total_price'          => $paymentAmount,
                'status'               => in_array(strtolower($paymentType), ['cash', 'pos'])
                    ? 'Success'
                    : $paymentStatus,
            ]);
        }
        // Update appointment billing status
        if ($is_two) {
            $types = $payments->pluck('payment_type')->map(fn($t) => strtolower($t))->toArray();

            if (in_array('cash', $types) && in_array('pos', $types)) {
                $salesLines = [];

                foreach ($payments as $p) {
                    $salesLines[] = [
                        "PaymentReference" => $p->payment_id
                            ?: ($p['reference_id'] ?? null),
                        "TotalAmount"      => floatval($p['total_price'] ?? 0),
                        "PaymentMethod"    => strtolower($p['payment_type']) === 'cash' ? "Cash" : "POS"
                    ];
                }

                $body = [
                    "_contract" => [
                        "worker" => $tech->tech_id,
                        "SalesOrderId" => $appointment->sales_order_id,
                        "BookId" => $appointment->book_id,
                        "Discount" => $appointment->discount_value ?? 0,
                        "salesLines" => $salesLines
                    ]
                ];
                $appointment->billing_status = 'paid';
                $appointment->collect = 0;
                $appointment->paid = $payments->sum(function ($p) {
                    return floatval($p['total_price'] ?? 0);
                });
                $appointment->save();
                $appointment->update([
                    'status' => 'processing'
                ]);
                $appointment->save();
                // CompleteSuccessPaymentsJob::dispatch($appointment, $body);
                $cmd = "php " . escapeshellarg(base_path('artisan')) . " payments:complete "
                    . escapeshellarg($appointment->id) . " "
                    . escapeshellarg(json_encode($body))
                    . " > /dev/null 2>&1 &";

                exec($cmd);
            }
        }
        $this->getAppointmentBySalesOrder($appointment->id);

        return $results;
    }


    public function appointmentPaymentUpdate($id, array $data)
    {
        $authUser = Auth::user();
        $appointment = Appointment::with('payments')->findOrFail($id);
        if (
            $appointment->technician_id !== $authUser->id &&
            !$authUser->hasPermissionTo('update appointments') // or any permission name
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to view this appointment.')->send();
        }

        $appointment->payments()->delete();

        foreach ($data['payments'] as $payment) {
            if (isset($payment['image']) && $payment['image']->isValid()) {
                $payment['image'] = $payment['image']->store('appointment_payments', 's3');
            }
            $appointment->payments()->create($payment);
        }

        return $appointment->fresh('payments');
    }

    public function getAppointmentPayments($appointmentId)
    {
        $appointment = Appointment::with('payments')->findOrFail($appointmentId);
        return $appointment->payments ?? [];
    }
    // create invoice for appointment
    public function createInvoice(Appointment $appointment): Invoice
    {
        $subTotal = 0;
        $invoiceItems = [];

        foreach ($appointment->items as $item) {
            $itemPrice = $item->price;
            $itemQty = $item->quantity;
            $itemDiscount = $item->discount ?? 0;
            $itemDiscount_value = $item->discount_value ?? 0;
            $itemDiscountType = $item->discount_type ?? 'fixed';
            $itemSubTotal = $itemPrice * $itemQty;
            if ($itemDiscountType === 'percentage') {
                $itemDiscount = ($itemSubTotal) * ($itemDiscount / 100);
            }
            $itemTotal = $itemSubTotal - $itemDiscount;

            $subTotal += $itemSubTotal;

            $invoiceItems[] = [
                'item_id' => $item->item_id,
                'item_name' => $item->name,
                'item_price' => $itemPrice,
                'item_qty' => $itemQty,
                'item_sub_total_price' => $itemSubTotal,
                'item_discount' => $itemDiscount,
                'discount_type' => $itemDiscountType,
                'discount_value' => $itemDiscount_value,
                'item_total_price' => $itemTotal,
            ];
        }

        $discount = $appointment->discount ?? 0;
        $discountType = $appointment->discount_type ?? 'fixed';
        $discountValue = $appointment->discount_value ?? 0;
        if ($discountType === 'percentage') {
            $discount = ($subTotal) * ($discount / 100);
        }
        $subTotalAfterDiscount = $subTotal - $discount;

        $vat = round(($subTotalAfterDiscount * 15) / 115, 2);
        $total = $subTotalAfterDiscount;

        $invoice = Invoice::create([
            'appointment_id' => $appointment->id,
            'invoice_status' => 'unpaid',
            'sub_total' => $subTotal,
            'discount' => $discount,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'vat' => $vat,
            'total' => $total,
        ]);

        foreach ($invoiceItems as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'item_price' => $item['item_price'],
                'item_qty' => $item['item_qty'],
                'item_sub_total_price' => $item['item_sub_total_price'],
                'item_discount' => $item['item_discount'],
                'discount_type' => $item['discount_type'],
                'discount_value' => $item['discount_value'],
                'item_total_price' => $item['item_total_price'],
            ]);
        }

        return $invoice;
    }

    // update appointment items and parts
    public function createAppointmentComponents($appointment, array $data)
    {
        // dd($data);
        $subTotal = 0;
        // dd($data);
        // Handle items with duplicate merging
        if (!empty($data['items'])) {
            $groupedItems = [];

            foreach ($data['items'] as $item) {
                $itemId = $item['item_id'];
                $qty = intval($item['quantity'] ?? 1);
                $discount = floatval($item['discount'] ?? 0);
                $discountType = $item['discount_type'] ?? 'fixed'; // default to fixed
                $discountValue = $item['discount_value'] ?? 0;
                $image = $item['image'] ?? null;

                if (!isset($groupedItems[$itemId])) {
                    $groupedItems[$itemId] = [
                        'quantity' => $qty,
                        'discount' => $discount,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'image' => $image,
                    ];
                } else {
                    $groupedItems[$itemId]['quantity'] += $qty;
                    $groupedItems[$itemId]['discount'] += $discount;
                    // optionally, you could normalize discount here based on type
                }
            }

            foreach ($groupedItems as $itemId => $groupedItem) {
                $model = Item::find($itemId);
                if (!$model) continue;

                $price = floatval($model->price ?? 0);
                $qty = $groupedItem['quantity'];
                $discount = $groupedItem['discount'];
                $discountType = $groupedItem['discount_type'];
                $discountValue = $groupedItem['discount_value'] ?? 0;
                $subTotalPrice = $price * $qty;

                if ($discountType === 'percentage') {
                    $discount = $subTotalPrice * ($discount / 100);
                }

                $totalPrice = $subTotalPrice - $discount;
                $subTotal += $totalPrice;

                $imagePath = null;
                if (isset($groupedItem['image']) && $groupedItem['image'] instanceof UploadedFile && $groupedItem['image']->isValid()) {
                    $imagePath = $groupedItem['image']->store('appointment_items', 's3');
                }

                $appointment->items()->create([
                    'item_id' => $model->id,
                    'serial' => $model->serial,
                    'code' => $model->code,
                    'name' => $model->name,
                    'description' => $model->description,
                    'price' => $price,
                    'quantity' => $qty,
                    'discount' => $discount,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                    'sub_total_price' => $subTotalPrice,
                    'total_price' => $totalPrice,
                    'image' => $imagePath,
                ]);
            }
        }

        // Handle parts
        $subTotal = 0;
        if (!empty($data['parts'])) {
            foreach ($data['parts'] as $part) {
                $model = Part::find($part['part_id']);
                if (!$model) continue;

                $qty = intval($part['quantity'] ?? 1);
                $discountType = $part['discount_type'] ?? 'fixed';
                $discountValue = floatval($part['discount'] ?? 0);
                $imagePath = null;

                if (isset($part['image']) && $part['image'] instanceof UploadedFile && $part['image']->isValid()) {
                    $imagePath = $part['image']->store('appointment_parts', 's3');
                }

                $isWarranty = false;
                $itemId = null;
                $newid = null;

                if (isset($part['periodic_main_item_id'])) {
                    $item = PeriodicMainItem::find($part['periodic_main_item_id']);
                    $newid = $item->id ?? null;
                    $itemId = $item->item_id ?? null;
                } elseif (isset($part['emergency_main_item_id'])) {
                    $item = EmergencyMainItem::find($part['emergency_main_item_id']);
                    $newid = $item->id ?? null;
                    $itemId = $item->item_id ?? null;
                }

                if ($itemId) {
                    $item = Item::find($itemId);
                    $warrantyYears = $item->warranty_period ?? 0;

                    $installation = AppointmentItem::where('item_id', $itemId)->first();
                    if ($installation && $installation->appointment?->appointment_date && $warrantyYears) {
                        $installDate = \Carbon\Carbon::parse($installation->appointment->appointment_date);
                        $warrantyEndDate = $installDate->addYears($warrantyYears);
                        $isWarranty = now()->lessThanOrEqualTo($warrantyEndDate);
                    }
                }

                $price = $isWarranty ? 0 : doubleval($model->price);
                $subTotalPrice = $isWarranty ? 0 : $price * $qty;

                // ✅ Recalculate discount properly inside loop
                $discount = 0;
                if (!$isWarranty) {
                    $discount = $discountType === 'percentage'
                        ? ($subTotalPrice * ($discountValue / 100))
                        : $discountValue;
                }

                $totalPrice = $subTotalPrice - $discount;
                $subTotal += $totalPrice;


                // dd($totalPrice, $subTotalPrice, $subTotal, $discount, $discountType, $discountValue);
                // Save part based on appointment type
                if ($appointment->type === 'emergency') {
                    $emergencyParts = EmergencyMainItemPart::create([
                        'appointment_id' => $appointment->id,
                        'emergency_main_item_id' =>   $newid,
                        'item_id' => $itemId,
                        'part_id' => $model->id,
                        'serial' => $model->serial,
                        'code' => $model->code,
                        'name' => $model->name,
                        'description' => $model->description,
                        'price' => $price,
                        'quantity' => $qty,
                        'discount' => $discount,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'sub_total_price' => $subTotalPrice,
                        'total_price' => $totalPrice,
                        'image' => $imagePath,
                    ]);
                } elseif ($appointment->type === 'periodic') {
                    $periodicParts = PeriodicMainItemPart::create([
                        'appointment_id' => $appointment->id,
                        'periodic_main_item_id' => $newid,
                        'item_id' => $itemId,
                        'part_id' => $model->id,
                        'serial' => $model->serial,
                        'code' => $model->code,
                        'name' => $model->name,
                        'description' => $model->description,
                        'price' => $price,
                        'quantity' => $qty,
                        'discount' => $discount,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'sub_total_price' => $subTotalPrice,
                        'total_price' => $totalPrice,
                        'image' => $imagePath,
                    ]);
                } else {
                    // fallback for installation appointments
                    $appointment->parts()->create([

                        'part_id' => $model->id,
                        'serial' => $model->serial,
                        'code' => $model->code,
                        'name' => $model->name,
                        'description' => $model->description,
                        'price' => $price,
                        'quantity' => $qty,
                        'discount' => $discount,
                        'discount_type' => $discountType,
                        'discount_value' => $discountValue,
                        'sub_total_price' => $subTotalPrice,
                        'total_price' => $totalPrice,
                        'image' => $imagePath,
                    ]);
                }
            }
        }



        return $subTotal;
    }

    // show difference between old invoice and new invoice
    protected function compareAppointmentAndInvoiceItems($appointmentItems, $invoiceItems)
    {
        $differences = [];

        // First: Compare counts
        if (count($appointmentItems) !== count($invoiceItems)) {
            $differences[] = [
                'issue' => 'Item count mismatch',
                'appointment_count' => count($appointmentItems),
                'invoice_count' => count($invoiceItems),
            ];
            // Optionally return early if count mismatch is critical
            // return $differences;
        }

        // Second: Compare individual items
        foreach ($appointmentItems as $appointmentItem) {
            $matchingInvoiceItem = collect($invoiceItems)->first(function ($invoiceItem) use ($appointmentItem) {
                return intval($invoiceItem['item_id']) === intval($appointmentItem['item_id']);
            });

            if (!$matchingInvoiceItem) {
                $differences[] = [
                    'item_id' => $appointmentItem['item_id'],
                    'issue' => 'Item not found in invoice',
                    'appointment' => $appointmentItem,
                    'invoice' => null,
                ];
                continue;
            }

            // Compare quantity and discount
            $quantityMatch = intval($appointmentItem['quantity']) === intval($matchingInvoiceItem['item_qty']);
            $discountMatch = floatval($appointmentItem['discount']) === floatval($matchingInvoiceItem['item_discount']);

            if (!$quantityMatch || !$discountMatch) {
                $differences[] = [
                    'item_id' => $appointmentItem['item_id'],
                    'issue' => 'Mismatch in quantity or discount',
                    'appointment' => [
                        'quantity' => $appointmentItem['quantity'],
                        'discount' => $appointmentItem['discount'],
                    ],
                    'invoice' => [
                        'quantity' => $matchingInvoiceItem['item_qty'],
                        'discount' => $matchingInvoiceItem['item_discount'],
                    ],
                ];
            }
        }

        return $differences;
    }

    // Deduct appointment items and parts from technician stock
    protected  function deductAppointmentFromTechStock(Appointment $appointment)
    {
        // Get the technician and their stock
        $technician = $appointment->technician;
        if (!$technician || !$technician->stock) {
            throw new \Exception('Technician or technician stock not found.');
        }

        $userStock = $technician->stock;
        // Load appointment items and parts (eager if not already loaded)
        $appointment->loadMissing(['items', 'parts']);
        // 🔻 Deduct Items
        foreach ($appointment->items as $item) {
            $stockItem = $userStock->items()->where('item_id', $item->item_id)->first();

            if ($stockItem) {
                $stockItem->decrement('quantity', $item->quantity);

                if ($stockItem->quantity <= 0) {
                    $stockItem->delete();
                }
            }
        }
        // 🔻 Deduct Parts
        foreach ($appointment->parts as $part) {
            $stockPart = $userStock->parts()->where('part_id', $part->part_id)->first();
            if ($stockPart) {
                $stockPart->decrement('quantity', $part->quantity);
                if ($stockPart->quantity <= 0) {
                    $stockPart->delete();
                }
            }
        }
    }
    // Deduct appointment lines from technician's warehouse
    protected function deductAppointmentLinesFromWarehouse(Appointment $appointment)
    {
        $technician = $appointment->technician;
        $warehouse  = $technician->stock; // UserStock

        if (!$warehouse) {
            throw new \Exception("Technician {$technician->id} does not have a warehouse assigned.");
        }

        $lines = $appointment->lines;

        if ($lines->isEmpty()) {
            throw new \Exception("Appointment {$appointment->id} has no lines to deduct.");
        }

        foreach ($lines as $line) {
            // ✅ Skip if not "تركيب"
            if ($line->type !== 'تركيب') {
                continue;
            }

            if (!$line->line_type) {
                Log::warning("Line {$line->id} has no type, skipping deduction.");
                continue;
            }

            if ($line->line_type === 'item') {
                $stock = $warehouse->items()
                    ->where('item_number', $line->item_number)
                    ->first();
            } elseif ($line->line_type === 'part') {
                $stock = $warehouse->parts()
                    ->where('item_number', $line->item_number)
                    ->first();
            } else {
                Log::warning("Unsupported line type [{$line->line_type}] on line {$line->id}");
                continue;
            }

            if (!$stock) {
                Log::error("Stock not found in warehouse {$warehouse->id} for item_number {$line->item_number}");
                continue;
            }

            if ($stock->quantity < $line->quantity) {
                Log::error("Insufficient stock in warehouse {$warehouse->id} for item_number {$line->item_number}");
                continue;
            }

            // ✅ Decrement only for تركيب lines
            $stock->quantity -= $line->quantity;

            if ($stock->quantity <= 0) {
                $stock->delete(); // remove if empty
                Log::info("Stock for item_number {$line->item_number} removed (quantity reached 0).");
            } else {
                $stock->save();
            }
        }

        return true;
    }


    //filter appointments
    public function filterAppointments($request)
    {
        $query = Appointment::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_status')) {
            $query->where('billing_status', $request->billing_status);
        }

        if ($request->filled('appointment_date_from') && $request->filled('appointment_date_to')) {
            $query->whereBetween('appointment_date', [
                $request->appointment_date_from,
                $request->appointment_date_to
            ]);
        }

        if ($request->filled('technician_id')) {
            $query->where('technician_id', $request->technician_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('power_socket')) {
            $query->where('power_socket', $request->power_socket);
        }

        $perPage = $request->input('per_page', 10);

        $appointments = $query->paginate($perPage);



        return $this->setCode(code: 200)->setData([
            'appointments' => GetAllAppointmentsResource::collection($appointments->items()),
            'current_page' => $appointments->currentPage(),
            'per_page' => $appointments->perPage(),
            'total' => $appointments->total(),
            'last_page' => $appointments->lastPage(),
        ])->setMessage('Success.')->send();
    }

    public function searchAppointments($request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('q');
        $customerId = $request->input('customer_id');
        $technicianId = $request->input('technician_id');

        $query = Appointment::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('appointment_num', 'like', "%$search%")

                    ->orWhere('whats_app', 'like', "%$search%")
                    ->orWhere('alternative_number', 'like', "%$search%")
                    ->orWhere('address', 'like', "%$search%")
                    ->orWhere('sector', 'like', "%$search%")
                    ->orWhere('cancel_notes', 'like', "%$search%")
                    ->orWhere('reschedule_notes', 'like', "%$search%");

                // Search in customer name

                $q->orWhereHas('customer', function ($query) use ($search) {
                    $query->where('username', 'like', "%$search%");
                });

                // Search in technician name
                $q->orWhereHas('technician', function ($query) use ($search) {
                    $query->where('username', 'like', "%$search%");
                });
            });
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        $appointments =  $query->paginate($perPage);


        return $this->setCode(code: 200)->setData([
            'appointments' => GetAllAppointmentsResource::collection($appointments->items()),
            'current_page' => $appointments->currentPage(),
            'per_page' => $appointments->perPage(),
            'total' => $appointments->total(),
            'last_page' => $appointments->lastPage(),
        ])->setMessage('Success.')->send();
    }


    // new instance methods
    public function newInstanceAppointment(int $appointmentId, array $data)
    {
        // Step 1: Retrieve the old appointment
        $oldAppointment = Appointment::findOrFail($appointmentId);
        $authUser = Auth::user();
        if (
            $oldAppointment->technician_id !== $authUser->id &&
            !$authUser->hasPermissionTo('create appointments') // or any permission name
        ) {
            return $this->setCode(code: 401)->setData([])->setMessage('You are not authorized to update this appointment.')->send();
        }
        // Initial values
        $subTotalPrice = 0;
        $discount = floatval($data['discount'] ?? 0);
        $discountType = $data['discount_type'] ?? 'fixed';
        // Step 2: Create the new appointment
        $newAppointment = Appointment::create([
            'customer_id' => $oldAppointment->customer_id,
            'technician_id' => $oldAppointment->technician_id,
            'address_id' => $oldAppointment->address_id,
            'type' => $data['type'],
            'service_type' => $data['service_type'] ?? $oldAppointment->maintenance_type,
            'phone' => $oldAppointment->phone,
            'whats_app' => $oldAppointment->whats_app,
            'alternative_number' => $oldAppointment->alternative_number,
            'address' => $oldAppointment->address,
            'latitude' => $oldAppointment->latitude,
            'longitude' => $oldAppointment->longitude,
            'branch' => $oldAppointment->branch,
            'sector' => $oldAppointment->sector,
            'status' => 'pending',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => now()->format('H:i'),
            'sub_total_price' => 0,
            'discount' => 0,
            'total_price' => 0,
            'paid' => 0,
            'collect' => 0,
            'discount_type' => 'fixed',
            'discount_value' => 0,
        ]);

        // Step 3: Add each item to the appropriate table
        foreach ($data['items'] as $itemData) {
            $item = Item::findOrFail($itemData['item_id']);

            $price = floatval($item->price);
            $quantity = intval($itemData['quantity'] ?? 1);
            $itemTotalPrice = 0;

            $itemPayload = [
                'appointment_id' => $newAppointment->id,
                'item_id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'code' => $item->code,
                'serial' => $item->serial,
                'image' => $item->image,
                'price' => $item->price,
                'status' => 'pending',
            ];

            if ($data['type'] === 'periodic') {
                $itemPayload['maintenance_type'] = $itemData['service_type'] ?? null;
                $newAppointment->periodicItems()->create($itemPayload);
            } elseif ($data['type'] === 'emergency') {
                $itemPayload['issues_reported_from_client'] = $itemData['issues_reported_from_client'] ?? '[]';
                $newAppointment->emergencyItems()->create($itemPayload);
            } elseif ($data['type'] === 'installation') {
                $itemPayload['quantity'] = $quantity;

                $itemTotalPrice = $price * $quantity;
                $subTotalPrice += $itemTotalPrice;

                $itemPayload['total_price'] = $itemTotalPrice;

                $newAppointment->items()->create($itemPayload);
            }
        }
        if ($data['type'] === 'installation') {
            if ($discountType === 'percentage') {
                $discount = $subTotalPrice * ($discount / 100);
            }

            $totalPrice = $subTotalPrice - $discount;

            $newAppointment->update([
                'sub_total_price' => $subTotalPrice,
                'discount' => $discount,
                'discount_type' => $discountType,
                'discount_value' => $discount,
                'total_price' => $totalPrice,
                'paid' => 0,
                'collect' => $totalPrice,
            ]);
        }

        return new AppointmentResource($newAppointment);
    }


    // complete appointment otp

    public function verifyOtp($appointment, $otp)
    {
        if (!$appointment || $appointment->complete_otp !== $otp) {
            return  $this->setCode(401)->setData([])->setMessage('Invalid OTP')->send();
        }
        return  $this->setCode(200)->setData([])->setMessage('OTP verified,Complete The appointment')->send();
    }

    // syncAllTechnicianAppointments
    public function syncAllTechnicianAppointments(array $appointments): void
    {

        return;
        DB::beginTransaction();

        // stop function  on this point to prevent any changes to the database until we are sure about the logic
        try {
            foreach ($appointments as $appointmentData) {
                // Get technician and customer by external technician_rec_id
                $technician = User::where('tech_id', $appointmentData['Worker'])->first();
                $customer = User::where(function ($query) use ($appointmentData) {
                    $query->where('technician_rec_id', $appointmentData['CustomerId']);

                    if (!empty($appointmentData['CustomerPhoneNumber'])) {
                        $query->orWhere('phone', $appointmentData['CustomerPhoneNumber']);
                    }
                })->first();
                if (!$customer) {
                    $customer = User::create([
                        'technician_rec_id' => $appointmentData['CustomerId'],
                        'username'          => $appointmentData['CustomerName'] ?? 'New Customer',
                        'phone'             => $appointmentData['CustomerPhoneNumber'] ?? null,
                        'email'             => $appointmentData['CustomerEmail'] ?? null,
                        'type'              => 'customer',
                        'address'           => $appointmentData['CityName'] ?? null,
                        'status'            => 'active',
                    ]);
                }
                // ✅ Skip this appointment if either technician or customer is missing
                if (!$technician || !$customer) {
                    continue;
                }
                // dd($customer);
                // Create or update appointment
                $typeMap = [
                    'تركيب'        => 'installation',
                    'خدمات'        => 'service',
                    'شكوى'         => 'installation',
                    'صيانة دورية'  => 'periodic',
                    'صيانة طارئة'  => 'emergency',
                ];
                $mappedType = $typeMap[$appointmentData['OrderTypeId']] ?? 'installation';

                $totalAmount = collect($appointmentData['SalesLines'] ?? [])
                    ->filter(fn($line) => empty($line['None']) || $line['None'] != 'None')
                    ->sum(fn($line) => (float) ($line['TotalAmount'] ?? 0));

                $appointment = Appointment::updateOrCreate(
                    ['sales_order_id' => $appointmentData['SalesOrderId']],
                    [
                        'rec_id' => $appointmentData['Id'],
                        'technician_id'     => $technician?->id,
                        'customer_id'       => $customer?->id,
                        'type'              => $mappedType,
                        'type_rec_id' => $appointmentData['OrderTypeRecId'] ?? null,
                        'appointment_date'  => $appointmentData['TransDate'] ?? null,
                        'from'              => $appointmentData['FromTime'] ?? null,
                        'to'                => $appointmentData['ToTime'] ?? null,
                        'phone'    => $appointmentData['CustomerPhoneNumber'] ?? null,
                        // 'sales_order_id'    => $appointmentData['SalesOrderId'] ?? null,
                        'book_id' => $appointmentData['BookId'],
                        'total_price'    => $totalAmount,
                        'collect'    => $totalAmount,
                        // new fields
                        'customer_confirmation_status' => $appointmentData['CustConfirmStatus'] ?? null,
                        'description_common_issues'    => $appointmentData['DescriptionCommonIssue'] ?? null,
                        'item_common_issues' => $appointmentData['ItemIdCommonIssue'] ?? null,
                        'order_notes' => $appointmentData['NotesOrder'] ?? null,
                        'warranty_status' => $appointmentData['WarrantyStatus'] ?? null,
                        'notes_optional' => $appointmentData['NotesOptional'] ?? null,
                        // book id

                        // status
                        'dy365_status' => $appointmentData['Status'] ?? null,
                        'tech_status' => $appointmentData['TechnicianStatus'] ?? null,
                        // conditionally set dy_completed
                        'dy_completed' => isset($appointmentData['Status']) && strtolower($appointmentData['Status']) === 'completed' ? 1 : 0,
                    ]
                );
                // Update appointment rec_id
                // Insert lines if not already present
                if (!empty($appointmentData['SalesLines']) && is_array($appointmentData['SalesLines'])) {

                    // Step 1: Clear existing lines before inserting new ones
                    $appointment->lines()->delete();
                    AppointmentLine::where('appointment_id', $appointment->id)->delete();

                    // Step 2: Insert new lines
                    foreach ($appointmentData['SalesLines'] as $line) {
                        $itemNumber = $line['ItemNumber'] ?? null;
                        $itemType = null;
                        $lineId = null;

                        if ($itemNumber) {
                            // Check if it's an Item
                            $item = Item::where('item_number', $itemNumber)->first();
                            if ($item) {
                                $itemType = 'item';
                                $lineId = $item->id;
                            } else {
                                // Check if it's a Part
                                $part = Part::where('item_number', $itemNumber)->first();
                                if ($part) {
                                    $itemType = 'part';
                                    $lineId = $part->id;
                                }
                            }
                        }

                        AppointmentLine::updateOrCreate(
                            [
                                'appointment_id' => $appointment->id,
                                'item_rec_id'    => $line['ProductRecId'],
                                'sales_line_id'  => $line['SaleslineId'] ?? null,
                                'item_number'    => $itemNumber,
                            ],
                            [
                                'quantity'       => $line['Quantity'] ?? 0,
                                'is_paid'        => $line['IsPaid'] ?? false,
                                'price'          => $line['UnitPrice'] ?? 0,
                                'total_amount'   => $line['TotalAmount'] ?? 0,
                                'type'           => $line['OrderTypeId'] ?? null,
                                'line_id'        => $lineId,
                                'line_type'      => $itemType,
                                'discount'       => $line['Discount'] ?? 0,
                                'discount_value' => $line['Discount'] ?? 0,
                                'discount_type'  => 'fixed',
                                'sales_history_date'    => $line['SalesHistoryDate'] ?? null,
                                'warranty_status'       => empty($line['WarrantyStatus']) ? 'No' : $line['WarrantyStatus'],
                                'description_common_issues' => $line['DescriptionCommonIssue'] ?? null,
                                'item_common_issues'         => $line['ItemCommonIssue'] ?? null,
                                'payment_method'             => $line['PaymentMethod'] ?? null,
                            ]
                        );
                    }

                    // Step 3: Update totals
                    $appointment->collect = $appointment->lines
                        ->where('is_paid', false)
                        ->where('warranty_status', '!=', 'Yes')
                        ->sum('total_amount');
                    $appointment->total_price = $appointment->collect;
                    $appointment->save();
                } else {
                    // ✅ If there are NO SalesLines, delete all existing ones
                    $appointment->lines()->delete();
                    AppointmentLine::where('appointment_id', $appointment->id)->delete();

                    // Optionally reset totals
                    $appointment->collect = 0;
                    $appointment->total_price = 0;
                    $appointment->save();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Technician appointments sync failed: " . $e->getMessage());
        }
    }

    // Sync all appointments for a technician
    public function newSyncAllTechnicianAppointments(array $appointments): void
    {
        return;
        DB::beginTransaction();

        try {
            $newCount = 0;
            $skippedCount = 0;

            foreach ($appointments as $appointmentData) {
                // ✅ Get technician and customer by external technician_rec_id
                $technician = User::where('tech_id', $appointmentData['Worker'])->first();

                $customer = User::where(function ($query) use ($appointmentData) {
                    $query->where('technician_rec_id', $appointmentData['CustomerId']);
                    if (!empty($appointmentData['CustomerPhoneNumber'])) {
                        $query->orWhere('phone', $appointmentData['CustomerPhoneNumber']);
                    }
                })->first();

                if (!$customer) {
                    $customer = User::create([
                        'technician_rec_id' => $appointmentData['CustomerId'],
                        'username'          => $appointmentData['CustomerName'] ?? 'New Customer',
                        'phone'             => $appointmentData['CustomerPhoneNumber'] ?? null,
                        'email'             => $appointmentData['CustomerEmail'] ?? null,
                        'type'              => 'customer',
                        'address'           => $appointmentData['CityName'] ?? null,
                        'status'            => 'active',
                    ]);
                }

                // ✅ Skip this appointment if either technician or customer is missing
                if (!$technician || !$customer) {
                    continue;
                }

                // ✅ Skip if appointment already exists
                if (Appointment::where('sales_order_id', $appointmentData['SalesOrderId'])->exists()) {
                    $skippedCount++;
                    continue;
                }

                // ✅ Map type
                $typeMap = [
                    'تركيب'        => 'installation',
                    'خدمات'        => 'service',
                    'شكوى'         => 'installation',
                    'صيانة دورية'  => 'periodic',
                    'صيانة طارئة'  => 'emergency',
                ];
                $mappedType = $typeMap[$appointmentData['OrderTypeId']] ?? 'installation';

                // ✅ Calculate total
                $totalAmount = collect($appointmentData['SalesLines'] ?? [])
                    ->filter(fn($line) => empty($line['None']) || $line['None'] != 'None')
                    ->sum(fn($line) => (float) ($line['TotalAmount'] ?? 0));

                // ✅ Create appointment
                $appointment = Appointment::create([
                    'rec_id' => $appointmentData['Id'],
                    'technician_id'     => $technician?->id,
                    'customer_id'       => $customer?->id,
                    'type'              => $mappedType,
                    'type_rec_id'       => $appointmentData['OrderTypeRecId'] ?? null,
                    'appointment_date'  => $appointmentData['TransDate'] ?? null,
                    'from'              => $appointmentData['FromTime'] ?? null,
                    'to'                => $appointmentData['ToTime'] ?? null,
                    'phone'             => $appointmentData['CustomerPhoneNumber'] ?? null,
                    'sales_order_id'    => $appointmentData['SalesOrderId'] ?? null,
                    'book_id'           => $appointmentData['BookId'],
                    'total_price'       => $totalAmount,
                    'collect'           => $totalAmount,
                    'customer_confirmation_status' => $appointmentData['CustConfirmStatus'] ?? null,
                    'description_common_issues'    => $appointmentData['DescriptionCommonIssue'] ?? null,
                    'item_common_issues'           => $appointmentData['ItemIdCommonIssue'] ?? null,
                    'order_notes'                 => $appointmentData['NotesOrder'] ?? null,
                    'warranty_status'             => $appointmentData['WarrantyStatus'] ?? null,
                    'notes_optional'              => $appointmentData['NotesOptional'] ?? null,
                    'dy365_status'                => $appointmentData['Status'] ?? null,
                    'tech_status'                 => $appointmentData['TechnicianStatus'] ?? null,
                    'dy_completed'                => isset($appointmentData['Status']) && strtolower($appointmentData['Status']) === 'completed' ? 1 : 0,
                ]);

                // ✅ Add Sales Lines if available
                if (!empty($appointmentData['SalesLines']) && is_array($appointmentData['SalesLines'])) {
                    foreach ($appointmentData['SalesLines'] as $line) {
                        $itemNumber = $line['ItemNumber'] ?? null;
                        $itemType = null;
                        $lineId = null;

                        if ($itemNumber) {
                            $item = Item::where('item_number', $itemNumber)->first();
                            if ($item) {
                                $itemType = 'item';
                                $lineId = $item->id;
                            } else {
                                $part = Part::where('item_number', $itemNumber)->first();
                                if ($part) {
                                    $itemType = 'part';
                                    $lineId = $part->id;
                                }
                            }
                        }

                        AppointmentLine::create([
                            'appointment_id' => $appointment->id,
                            'item_rec_id'    => $line['ProductRecId'] ?? null,
                            'sales_line_id'  => $line['SaleslineId'] ?? null,
                            'item_number'    => $itemNumber,
                            'quantity'       => $line['Quantity'] ?? 0,
                            'is_paid'        => $line['IsPaid'] ?? false,
                            'price'          => $line['UnitPrice'] ?? 0,
                            'total_amount'   => $line['TotalAmount'] ?? 0,
                            'type'           => $line['OrderTypeId'] ?? null,
                            'line_id'        => $lineId,
                            'line_type'      => $itemType,
                            'discount'       => $line['Discount'] ?? 0,
                            'discount_value' => $line['Discount'] ?? 0,
                            'discount_type'  => 'fixed',
                            'sales_history_date'    => $line['SalesHistoryDate'] ?? null,
                            'warranty_status'       => empty($line['WarrantyStatus']) ? 'No' : $line['WarrantyStatus'],
                            'description_common_issues' => $line['DescriptionCommonIssue'] ?? null,
                            'item_common_issues'         => $line['ItemCommonIssue'] ?? null,
                            'payment_method'             => $line['PaymentMethod'] ?? null,
                        ]);
                    }

                    // ✅ Update totals
                    $appointment->collect = $appointment->lines
                        ->where('is_paid', false)
                        ->where('warranty_status', '!=', 'Yes')
                        ->sum('total_amount');
                    $appointment->total_price = $appointment->collect;
                    $appointment->save();
                } else {
                    $appointment->collect = 0;
                    $appointment->total_price = 0;
                    $appointment->save();
                }

                $newCount++;
            }

            DB::commit();

            Log::info("✅ Technician appointments sync completed. Inserted: $newCount, Skipped: $skippedCount");
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Technician appointments sync failed: " . $e->getMessage());
        }
    }

    // handle sales line

    public function handleSalesLine(array $data): array
    {
        $appointment = Appointment::where('id', $data['appointment_id'])->first();

        if (!$appointment) {
            return ['status' => false, 'message' => 'Appointment not found.'];
        }
        // dd($data);
        $rec_id = (int) $appointment->rec_id;

        switch ($data['action']) {
            case 'add':
                $payload = [
                    '_contract' => [
                        'appointment' => (int)$rec_id,
                        'items'       => $data['items'],
                    ],
                ];
                $response = $this->dyService->addSalesLine($payload);
                break;

            case 'update':
                $payload = [
                    '_contract' => [
                        'appointment' => $rec_id,
                        'salesLines'  => array_map(function ($line) {
                            return [
                                'salesLineRecId' => (int) $line['sales_line_id'],
                                'quantity'       => $line['quantity'],
                                'warrantyStatus' => $line['warrantyStatus'] ?? null,
                                'paymentMethod'  => $line['paymentMethod'] ?? null,

                            ];
                        }, $data['salesLines']), // expect: ['sales_lines' => [['sales_line_id' => .., 'quantity' => ..], ...]]
                    ],
                ];
                // dd($payload);
                $response = $this->dyService->updateSalesLine($payload);
                break;

            case 'delete':
                $payload = [
                    '_contract' => [
                        'appointment' => $rec_id,
                        'salesLines'  => array_map(function ($id) {
                            return (int) $id;
                        }, $data['salesLines']), // expect: ['sales_line_ids' => [id1, id2, ...]]
                    ],
                ];
                $response = $this->dyService->deleteSalesLine($payload);
                break;

            default:
                return ['status' => false, 'message' => 'Invalid action'];
        }


        $this->getAppointmentBySalesOrder($appointment->id);

        if (isset($response['Error'])) {
            return ['status' => false, 'message' => $response['Error']];
        }

        return ['status' => true, 'data' => $response];
    }

    // Sync single appointment
    public function getAppointmentBySalesOrder($id)
    {
        $salesOrderId = Appointment::where('id', $id)->value('sales_order_id');
        $appointment = $this->dyService->getAppointmentBySalesOrder($salesOrderId);

        if (isset($appointment['Data']['Appointments']) && is_array($appointment['Data']['Appointments'])) {
            $this->syncAllTechnicianAppointments($appointment['Data']['Appointments']);
            return response()->json(['message' => 'Appointment synced successfully']);
        } else {

            return response()->json(['message' => 'Failed to sync Appointment: No products found'], 500);
        }
    }

    // get pending appointments
    public function getPendingDyAppointments(int $techId)
    {
        return Appointment::where('technician_id', $techId)
            ->where('dy_completed', 0)
            ->whereIn('status', ['Completed', 'complete'])
            ->with(['customer', 'technician', 'lines'])
            ->get();
    }

    // get last appointment payment
    public function getLastAppointmentPayment(int $appointmentId, string $type)
    {
        return AppointmentPayment::where('appointment_id', $appointmentId)
            ->where('payment_type', $type)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    // apply discount to appointment
    public function applyDiscount(int $appointmentId, float $discountValue, string $discountType)
    {
        // Find appointment
        $appointment = Appointment::findOrFail($appointmentId);

        // Ensure the authenticated user is the assigned technician
        $user = Auth::user();
        if ($user->id !== $appointment->technician_id) {
            abort(403, 'You are not authorized to modify this appointment.');
        }

        // Calculate new total
        $subTotal = $appointment->total_price ?? 0;

        if ($discountType === 'percentage') {
            $discountAmount = ($subTotal * $discountValue) / 100;
        } else {
            $discountAmount = $discountValue;
        }

        $newTotal = max(0, $subTotal - $discountAmount);

        // Update appointment
        $appointment->update([
            'discount'       => $discountValue,
            'discount_type'  => $discountType,
            'discount_value' => $discountAmount,
            'total_price'    => $newTotal,
            'collect'        => $newTotal - ($appointment->paid ?? 0),
        ]);

        return $appointment->fresh();
    }

    // delete appointment with sales lines

}
