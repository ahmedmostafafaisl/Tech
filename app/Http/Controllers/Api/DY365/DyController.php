<?php

namespace App\Http\Controllers\Api\DY365;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\DY365\DyCheckoutRequest;
use App\Http\Requests\Product\AddBundleProductsToAppointmentRequest;
use App\Http\Requests\Product\GetProductsRequest;
use App\Models\Appointment;
use App\Models\AppointmentBundle;
use App\Models\DyPaymentLink;
use App\Models\ShortLink;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;
use App\Repositories\Interfaces\TechRepositoryInterface;
use App\Repositories\Interfaces\TransferOrderInterface;
use App\Repositories\Interfaces\WarehouseInterface;
use App\Services\CheckCompleteStatus\CheckCompleteService;
use App\Services\DY365\DyService;
use App\Services\Logs\TechnicianAppointmentLogService;
use App\Services\Payment\TabbyService;
use App\Services\Payment\TamaraService;
use App\Services\TaqnyatSmsService;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DyController extends Controller
{
    use ApiResponseHelper;
    public $dy_service;

    protected $warehouseRepo;
    protected $techRepository;
    protected $appointmentRepo;
    protected $tabbyService;
    protected $tamaraService;
    protected $transferOrderRepo;
    protected $taqnyatSmsService;

    protected $checkCompleteService;

    public function __construct(
        AppointmentRepositoryInterface $appointmentRepo,
        DyService $dy_service,
        WarehouseInterface $warehouseRepo,
        TechRepositoryInterface $techRepository,
        TabbyService $tabbyService,
        TamaraService $tamaraService,
        TransferOrderInterface $transferOrderRepo,
        TaqnyatSmsService $taqnyatSmsService,
        CheckCompleteService $checkCompleteService
    ) {
        $this->dy_service = $dy_service;
        $this->warehouseRepo = $warehouseRepo;
        $this->techRepository = $techRepository;
        $this->tabbyService = $tabbyService;
        $this->tamaraService = $tamaraService;
        $this->transferOrderRepo = $transferOrderRepo;
        $this->taqnyatSmsService = $taqnyatSmsService;
        $this->checkCompleteService = $checkCompleteService;
    }



    // get warehouses // done
    public function getWarehouses()
    {
        $warehouses = $this->dy_service->getWarehouses();

        if (isset($warehouses['Data']['Warehouses']) && is_array($warehouses['Data']['Warehouses'])) {
            $this->warehouseRepo->syncWarehouses($warehouses['Data']['Warehouses']);
            return response()->json(['message' => 'Warehouses synced successfully.', 'data' => $warehouses['Data']['Warehouses']], 200);
        }

        return response()->json(['message' => 'No warehouses found.'], 404);
    }

    // get categories
    public function getProductCategories()
    {
        return $this->dy_service->getProductCategories();
    }


    // get payment methods
    public function getPaymentMethods()
    {
        return $this->dy_service->getPaymentMethods();
    }


    // get technicians // done
    public function getTechnicians()
    {
        $response = $this->dy_service->getTechnicians();

        if (isset($response['data']['Data']['Technicians']) && is_array($response['data']['Data']['Technicians'])) {
            return $response['data']['Data']['Technicians'];
        }

        return response()->json(['message' => 'No Technicians found.'], 404);
    }

    // get customers
    public function getCustomers(Request $request)
    {
        $payload = [
            'currentPage' => $request->input('currentPage', 1),
            'pageSize' => $request->input('pageSize', 100),
        ];
        $response = $this->dy_service->getCustomers($payload);

        if (isset($response['Data']['Customers']) && is_array($response['Data']['Customers'])) {
            // Sync customers with your local database
            $customers = $response['Data']['Customers'];
            $this->techRepository->syncCustomers($customers);
            return response()->json(['message' => 'Customers synced successfully.'], 200);
        }

        return response()->json(['message' => 'No Customers found.'], 404);
    }

    // return payment links
    public function getPaymentLinks(DyCheckoutRequest $request)
    {
        $validated = $request->validated();

        return $this->dy_service->getPaymentLinks($validated);
    }

    // Tabby success
    public function success(Request $request, $reference_id)
    {
        $payment = DyPaymentLink::where('payment_method', $request->payment_method)->where('dy_reference_id', $request->reference_id)->first();
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }

        // start  payment status
        $this->handlePaymentStatus($payment, "Approved");
        // end  payment status

        if ($request->payment_method == 'tabby') {
            $payment->update([
                'status' => 'success',
                'payment_id' => $request->payment_id ?? null,
            ]);
        } else if ($request->payment_method == 'tamara') {
            $orderId = $request->orderId;
            $statusResponse = app(TamaraService::class)->getOrderStatus($orderId);
            if (isset($statusResponse['status'])) {
                if ($statusResponse['status'] === 'approved') {
                    $authResponse = app(TamaraService::class)->authorizeOrder($orderId);
                    // $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $orderId);
                    if (isset($authResponse['status']) && $authResponse['status'] === 'authorised') {
                        $captureResponse = app(TamaraService::class)->captureOrderDy($reference_id, $orderId);
                    }
                } elseif ($statusResponse['status'] === 'authorised') {
                    $captureResponse = app(TamaraService::class)->captureOrderDy($reference_id, $orderId);
                }

                // Mark success regardless of status flow

            }
            $payment->update([
                'status' => 'success',
                'payment_id' => $request->orderId ?? null,
            ]);
        } else if ($request->payment_method == 'clickpay') {
            $payment->update([
                'status' => 'success',
            ]);
        }

        $totalAmount = $payment->amount; // this includes 15% VAT

        $priceWithoutTax = round($totalAmount / 1.15, 2); // base price
        $taxAmount = round($totalAmount - $priceWithoutTax, 2); // 15% VAT

        return view('Payment.result', [
            'status' => 'success',
            'payment_type' => $request->payment_method,
            'payment' => $payment,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }

    public function cancel(Request $request, $reference_id)
    {

        $payment = DyPaymentLink::where('payment_method', $request->payment_method)->where('dy_reference_id', $request->reference_id)->first();
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }

        // start  payment status
        $this->handlePaymentStatus($payment, "Canceled");
        // end  payment status

        if ($request->payment_method == 'tabby') {
            $payment->update([
                'status' => 'cancelled',
                'payment_id' => $request->payment_id ?? null,
            ]);
        } else if ($request->payment_method == 'tamara') {
            $payment->update([
                'status' => 'cancelled',
                'payment_id' => $request->orderId ?? null,
            ]);
        } else if ($request->payment_method == 'clickpay') {
            $payment->update([
                'status' => 'cancelled',
            ]);
        }
        // ❌ Handle canceled payment
        $totalAmount = $payment->amount; // this includes 15% VAT

        $priceWithoutTax = round($totalAmount / 1.15, 2); // base price
        $taxAmount = round($totalAmount - $priceWithoutTax, 2); // 15% VAT

        return view('Payment.result', [
            'status' => 'cancelled',
            'payment_type' => $request->payment_method,
            'payment' => $payment,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }

    public function failure(Request $request, $reference_id)
    {
        $payment = DyPaymentLink::where('payment_method', $request->payment_method)->where('dy_reference_id', $request->reference_id)->first();
        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }
        // start  payment status
        $this->handlePaymentStatus($payment, "Rejected");
        // end  payment status

        if ($request->payment_method == 'tabby') {
            $payment->update([
                'status' => 'failed',
                'payment_id' => $request->payment_id ?? null,
            ]);
        } else if ($request->payment_method == 'tamara') {
            $payment->update([
                'status' => 'failed',
                'payment_id' => $request->orderId ?? null,
            ]);
        } else if ($request->payment_method == 'clickpay') {
            $payment->update([
                'status' => 'failed',
            ]);
        }
        // ⚠️ Handle failed payment
        $totalAmount = $payment->amount; // this includes 15% VAT

        $priceWithoutTax = round($totalAmount / 1.15, 2); // base price
        $taxAmount = round($totalAmount - $priceWithoutTax, 2); // 15% VAT

        return view('Payment.result', [
            'status' => 'failed',
            'payment_type' => $request->payment_method,
            'payment' => $payment,
            'priceWithoutTax' => $priceWithoutTax,
            'taxAmount' => $taxAmount,
        ]);
    }

    public function tamaraWebhook(Request $request)
    {
        // Validate webhook signature (Tamara sends this in the header)
        $signature = $request->header('Tamara-Signature');
        // Optional: verify $signature against your webhook secret if configured

        $payload = $request->all();

        // Tamara webhook sends: order_id, order_reference_id, event_type, etc.
        $orderId          = $payload['order_id']           ?? null;
        $referenceId      = $payload['order_reference_id'] ?? null;   // e.g. PAY-000002466
        $eventType        = $payload['event_type']         ?? null;   // order_approved, order_expired, etc.

        if (!$orderId || !$referenceId) {
            return response()->json(['status' => 'error', 'message' => 'Missing required fields.'], 400);
        }

        // Find the payment record
        $payment = DyPaymentLink::where('payment_method', 'tamara')
            ->where('dy_reference_id', $referenceId)
            ->first();

        if (!$payment) {
            return response()->json(['status' => 'error', 'message' => 'Payment not found.'], 404);
        }

        // Avoid reprocessing already completed payments
        if ($payment->status === 'success') {
            return response()->json(['status' => 'ok', 'message' => 'Already processed.'], 200);
        }

        switch ($eventType) {

            case 'order_approved':
                // Authorize the order
                $authResponse = app(TamaraService::class)->authorizeOrder($orderId);

                if (isset($authResponse['status']) && $authResponse['status'] === 'authorised') {
                    app(TamaraService::class)->captureOrderDy($referenceId, $orderId);
                }

                $this->handlePaymentStatus($payment, 'Approved');

                $payment->update([
                    'status'     => 'success',
                    'payment_id' => $orderId,
                ]);
                break;

            case 'order_authorised':
                // Order was already authorised, just capture
                app(TamaraService::class)->captureOrderDy($referenceId, $orderId);

                $this->handlePaymentStatus($payment, 'Approved');

                $payment->update([
                    'status'     => 'success',
                    'payment_id' => $orderId,
                ]);
                break;

            case 'order_captured':
                // Capture confirmed by Tamara — ensure our record is marked success
                if ($payment->status !== 'success') {
                    $this->handlePaymentStatus($payment, 'Approved');
                    $payment->update([
                        'status'     => 'success',
                        'payment_id' => $orderId,
                    ]);
                }
                break;

            case 'order_expired':
            case 'order_declined':
            case 'order_cancelled':
                $this->handlePaymentStatus($payment, 'Declined');
                $payment->update(['status' => 'failed']);
                break;

            default:
                // Unknown event — log it but return 200 so Tamara stops retrying
                Log::warning('Tamara webhook unknown event', ['event' => $eventType, 'payload' => $payload]);
                break;
        }

        return response()->json(['status' => 'ok'], 200);
    }

    // handle payment status
    public function handlePaymentStatus($payment, $status)
    {
        $payload = [
            "_contract" => [
                "PaymentLinkId" => $payment->payment_reference_id,
                "PaymentStatus" => $status,
                "ReferenceId" => $payment->dy_reference_id,
            ],
        ];

        // Send payment status to service
        $dyStatus = $this->dy_service->dyPaymentStatus($payload);
    }
    //
    public function getPaymentStatus(Request $request)
    {
        try {
            $data = $request->validate([
                'payment_method' => 'required|string|in:tabby,tamara',
                'reference_id' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'paymentStatus' => (string) "null",
                'referenceId' => (string) "null",
                'error' => 'Payment not found.',
            ], 404);
        }

        $payment = DyPaymentLink::where('payment_method', $data['payment_method'])
            ->where('dy_reference_id', $data['reference_id'])
            ->orWhere('payment_reference_id', $data['reference_id'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$payment) {
            return response()->json([
                'status' => false,
                'paymentStatus' => (string) "null",
                'referenceId' => (string) "null",
                'error' => (string) 'Payment not found.',
            ], 404);
        }

        $statusMap = [
            'pending' => 'Pending',
            'success' => 'Approved',
            'failed' => 'Rejected',
            'canceled' => 'Canceled',
        ];

        $mappedStatus = $statusMap[strtolower($payment->status)] ?? 'Pending';

        return response()->json([
            'status' => strtolower($payment->status) === 'success',
            'paymentStatus' => $mappedStatus,
            'referenceId' => strtolower($payment->status) === 'success' ? $payment->dy_reference_id : (string) "null",
            'error' => (string) "null",
        ]);
    }


    // get technician stock
    public function getTechnicianStock(Request $request)
    {
        $users = User::where('type', 'tech')->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No technicians found.'], 404);
        }

        $allStocks = [];

        foreach ($users as $user) {
            $payload = [
                'worker' => $user->technician_rec_id,
            ];

            $response = $this->dy_service->getTechnicianStock($payload);

            // You may need to decode JSON if the service returns a JSON string
            $stock = is_string($response) ? json_decode($response, true) : $response;

            $allStocks[] = [
                'technician_id' => $user->id,
                'technician_name' => $user->username ?? $user->username,
                'technician_rec_id' => $user->technician_rec_id,
                'stock' => $stock ?? [],
            ];
        }

        return response()->json($allStocks);
    }

    // get warehouse stock
    public function getWarehouseStock(Request $request)
    {
        $warehouses = Warehouse::all();

        if ($warehouses->isEmpty()) {
            return response()->json(['message' => 'No warehouses found.'], 404);
        }

        return   $allStocks = [];

        foreach ($warehouses as $warehouse) {
            $payload = [
                'warehouseId' => $warehouse->invent_location_id,
            ];

            return $response = $this->dy_service->getWarehouseStock($payload);

            // You may need to decode JSON if the service returns a JSON string
            $stock = is_string($response) ? json_decode($response, true) : $response;

            $allStocks[] = [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->name ?? $warehouse->name,
                'invent_location_id' => $warehouse->invent_location_id,
                'stock' => $stock ?? [],
            ];
        }

        return response()->json($allStocks);
    }

    // get technician Transfers
    public function getTechnicianTransfers(Request $request)
    {
        $users = User::where('type', 'tech')->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No technicians found.'], 404);
        }

        $allStocks = [];

        foreach ($users as $user) {
            $payload = [
                'worker' => $user->technician_rec_id,
            ];

            $response = $this->dy_service->getTechnicianTransfers($payload);



            // You may need to decode JSON if the service returns a JSON string
            $transfers = is_string($response) ? json_decode($response, true) : $response;

            $allStocks[] = [
                'technician_id' => $user->id,
                'technician_name' => $user->username ?? $user->username,
                'technician_rec_id' => $user->technician_rec_id,
                'transfers' => $transfers ?? [],
            ];
        }

        return response()->json($allStocks);
    }

    // get appointments
    public function getAppointments(Request $request)
    {
        $response = $this->dy_service->getAppointments();
        return response()->json($response);
    }
    // get technician appointments
    public function getTechnicianAppointments(Request $request)
    {
        $users = User::where('type', 'tech')->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No technicians found.'], 404);
        }

        $allAppointments = [];

        foreach ($users as $user) {
            $payload = [
                'worker' => $user->technician_rec_id,
            ];

            $response = $this->dy_service->getTechnicianAppointments($payload);

            // You may need to decode JSON if the service returns a JSON string
            $appointments = is_string($response) ? json_decode($response, true) : $response;

            $allAppointments[] = [
                'technician_id' => $user->id,
                'technician_name' => $user->username ?? $user->username,
                'technician_rec_id' => $user->technician_rec_id,
                'appointments' => $appointments ?? [],
            ];
        }
        return response()->json($allAppointments);
    }

    // get technician change status requests
    public function getTechnicianChangeStatusRequests(Request $request)
    {
        $users = User::where('type', 'tech')->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No technicians found.'], 404);
        }

        $allRequests = [];

        foreach ($users as $user) {
            $payload = [
                'worker' => $user->tech_id,
            ];

            $response = $this->dy_service->getTechnicianChangeStatusRequests($payload);

            // You may need to decode JSON if the service returns a JSON string
            $requests = is_string($response) ? json_decode($response, true) : $response;

            $allRequests[] = [
                'technician_id' => $user->id,
                'technician_name' => $user->username ?? $user->username,
                'technician_rec_id' => $user->technician_rec_id,
                'change_status_requests' => $requests ?? [],
            ];
        }

        return response()->json($allRequests);
    }

    // add sales line to appointment
    public function addSalesLine(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'orderTypeId' => 'required|string|in:تركيب,خدمات,شكوى,صيانة دورية,صيانة طارئة',
            'items' => 'required|array|min:1',
            'items.*.ItemNumber' => 'required|string|exists:items,item_number',
            'items.*.Quantity' => 'required|numeric|min:1',
            'tech_id' => 'nullable|integer',
            'book_id' => 'required|string|max:255',
        ]);

        // check Authorize tech or fail
        $this->checkCompleteService->authorizeTechOrFail(
            $validated['tech_id'] ?? null,
            "Tech ID mismatch detected before updating appointment {$validated['appointment_id']}"
        );
        $appointment = Appointment::where('id', $validated['appointment_id'])->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        $payload = [
            '_contract' => [
                'appointment' => $appointment->type_rec_id,
                'orderTypeId' => $validated['orderTypeId'],
                'items' => $validated['items'],
            ],
        ];

        $response = $this->dy_service->addSalesLine($payload);
        // \Artisan::call('technicians:sync-appointments');
        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 400);
        }

        return response()->json($response);
    }

    // update sales line to appointment
    public function updateSalesLine(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'sales_line_id' => 'required|integer|exists:appointment_lines,sales_line_id',
            'quantity' => 'required|numeric|min:1',
            'tech_id' => 'nullable|integer',
            'book_id' => 'required|string|max:255',
        ]);

        // check Authorize tech or fail
        $this->checkCompleteService->authorizeTechOrFail(
            $validated['tech_id'] ?? null,
            "Tech ID mismatch detected before updating appointment {$validated['appointment_id']}"
        );
        $appointment = Appointment::where('id', $validated['appointment_id'])->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        $payload = [
            'appointment' => (int) $appointment->type_rec_id,
            'salesLineId' => (int) $validated['sales_line_id'],
            'quantity' => $validated['quantity'],
            // 'items'        => $validated['items'],

        ];

        $response = $this->dy_service->updateSalesLine($payload);
        // \Artisan::call('technicians:sync-appointments');
        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 400);
        }

        return response()->json($response);
    }

    // delete sales line from appointment
    public function deleteSalesLine(Request $request)
    {
        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'sales_line_id' => 'required|integer|exists:appointment_lines,sales_line_id',
            'tech_id' => 'nullable|integer',
            'book_id' => 'required|string|max:255',
        ]);

        // check Authorize tech or fail
        $this->checkCompleteService->authorizeTechOrFail(
            $validated['tech_id'] ?? null,
            "Tech ID mismatch detected before updating appointment {$validated['appointment_id']}"
        );
        $appointment = Appointment::where('id', $validated['appointment_id'])->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        $payload = [
            'appointment' => (int) $appointment->type_rec_id,
            'salesLineId' => (int) $validated['sales_line_id'],
        ];

        $response = $this->dy_service->deleteSalesLine($payload);
        \Artisan::call('technicians:sync-appointments');
        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 400);
        }

        return response()->json($response);
    }

    // complete success payments
    public function completeSuccessPayments(Request $request)
    {
        $validated = $request->validate([
            'technician_id' => 'required',
            'SaleslineId' => 'required',
            'PaymentReference' => 'nullable|string',
            'TotalAmount' => 'required|numeric',
            'PaymentMethod' => 'required|string',
        ]);

        // Build payload in the desired format
        $payload = [
            "_contract" => [
                "worker" => $validated['technician_id'],
                "salesLines" => [
                    [
                        "SaleslineId" => $validated['SaleslineId'],
                        "PaymentReference" => $validated['PaymentReference'] ?? '',
                        "TotalAmount" => $validated['TotalAmount'],
                        "PaymentMethod" => $validated['PaymentMethod']
                    ]
                ]
            ]
        ];

        // Call the service
        $response = $this->dy_service->completeSuccessPayments($payload);

        if (isset($response['Error'])) {
            return response()->json(['status' => false, 'message' => $response['Error']], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment completed successfully',
            'data' => $response
        ]);
    }

    // get or create invoice
    public function getOrCreateInvoice($id)
    {
        $appointment = Appointment::where('id', $id)->first();
        if (!$appointment) {
            return response()->json(['message' => 'Appointment not found.'], 404);
        }

        $payload = [
            'salesOrderId' => $appointment->sales_order_id,
        ];


        return $response = $this->dy_service->getOrCreateInvoice($payload);

        if (isset($response['Error'])) {
            return response()->json(['message' => $response['Error']], 400);
        }
    }

    // send pdf link
    public function sendInvoice(Request $request, $appointmentId)
    {
        $request->validate([
            'phone' => 'required|string',
            'file' => 'required|file|mimes:pdf|max:20480',
            'book_id' => 'nullable|string',
        ]);

        if (empty($appointmentId)) {
            return response()->json(['success' => false, 'message' => 'Appointment ID missing'], 400);
        }

        if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
            return response()->json(['success' => false, 'message' => 'Invalid file upload'], 422);
        }

        $pdf = $request->file('file');
        $fileName = uniqid('invoice_', true) . '.' . $pdf->getClientOriginalExtension();

        try {
            // ✅ Upload to S3 (NO visibility to avoid ACL issues)
            $pdfPath = $pdf->storeAs("invoices/{$appointmentId}", $fileName, 's3');

            if (!$pdfPath) {
                Log::error('S3 upload failed (storeAs returned false)', [
                    'appointment_id' => $appointmentId,
                    'file' => $fileName,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload file to S3'
                ], 500);
            }

            // ✅ Public URL (works if bucket policy allows public read for invoices/*)
            $pdfUrl = Storage::disk('s3')->url($pdfPath);

            // ✅ Create short link (domain changes automatically by APP_URL)
            $code = Str::random(8);

            // ensure uniqueness (small loop to avoid rare collisions)
            while (ShortLink::where('code', $code)->exists()) {
                $code = Str::random(8);
            }

            ShortLink::create([
                'code' => $code,
                'url' => $pdfUrl,
                'expires_at' => null, // or now()->addDays(30)
                'book_id' => $request->book_id,
                'sent' => false,
            ]);

            $shortUrl = rtrim(config('app.url'), '/') . "/i/{$code}";

            // ✅ Send short link via SMS (instead of long S3 url)
            $smsResponse = $this->taqnyatSmsService->sendPdfLink($request->phone, $shortUrl);

            if (is_array($smsResponse) && isset($smsResponse['statusCode']) && (int) $smsResponse['statusCode'] === 201) {
                // Update sent = true
                ShortLink::where('code', $code)->update([
                    'sent' => true
                ]);
            } else {
                Log::warning('SMS إرسال فشل', [
                    'appointment_id' => $appointmentId,
                    'phone' => $request->phone,
                    'response' => $smsResponse,
                ]);
            }

            return response()->json([
                'success' => isset($smsResponse['statusCode']) && (int) $smsResponse['statusCode'] === 201,
                'file_url' => $pdfUrl,
                'short_url' => $shortUrl,
                'path' => $pdfPath,
                'sms' => $smsResponse,
            ]);
        } catch (\Throwable $e) {
            Log::error('sendInvoice exception', [
                'appointment_id' => $appointmentId,
                'file' => $fileName,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // get sales history
    public function getSalesHistory($id)
    {
        $payload = [
            'salesLineId' => $id,
        ];
        return $response = $this->dy_service->getSalesHistory($payload);
    }

    // submit customer change request
    public function submitCustomerChangeRequest(Request $request)
    {
        $payload = [
            'customerRecId' => $request->customer_id,
            'salesOrderId' => $request->sales_order_id,
            'requestType' => $request->request_type  // 0 for cancel , 1 for reschedule
        ];
        return $response = $this->dy_service->getCustomerChangeRequests($payload);
    }

    // get customer change requests
    public function getCustomerChangeRequests($id)
    {
        $payload = [
            'customer' => $id,
        ];

        return $response = $this->dy_service->getCustomerChangeRequests($payload);
    }

    // get products with caching and circuit breaker
    public function getProducts(GetProductsRequest $request)
    {
        $payload = [
            'warehouseId' => $request->input('warehouseId'),
            'type'        => $request->input('type') ?? 'Bundle',
            'currentPage' => $request->input('currentPage', 1),
            'pageSize'    => 200,
        ];

        $response = $this->dy_service->getProducts($payload);

        if (!isset($response['Data']['Products'])) {
            return $response;
        }

        $search = trim($request->input('search', ''));

        // ── Get existing bundle IDs for this appointment ──────────────
        $existingBundleIds = collect();

        if ($request->filled('book_id')) {
            $existingBundleIds = AppointmentBundle::query()
                ->where('book_id', $request->input('book_id'))
                ->where('status', 'added')
                ->pluck('bundle_id')
                ->map(fn($id) => strtolower($id));
        }

        // ── Filter products ───────────────────────────────────────────
        $products = collect($response['Data']['Products'])
            ->filter(function ($product) use ($existingBundleIds, $search) {

                // remove already added bundles
                $isExisting = !empty($product['ItemNumber']) &&
                    $existingBundleIds->contains(strtolower($product['ItemNumber']));

                if ($isExisting) {
                    return false;
                }

                // search filter
                if (!empty($search)) {

                    $itemNumber = strtolower($product['ItemNumber'] ?? '');
                    $name       = strtolower($product['Name'] ?? '');
                    $searchTerm = strtolower($search);

                    return str_contains($itemNumber, $searchTerm)
                        || str_contains($name, $searchTerm);
                }

                return true;
            })
            ->values();

        $response['Data']['Products'] = $products->toArray();
        $response['Data']['ProductsCount'] = $products->count();

        return $response;
    }

    // get bundle products
    public function getBundleProducts(Request $request)
    {
        $auth_user = auth()->user();
        $payload = [
            'itemId' => $request->input('itemId'),
            'workerRecId' => $request->tech_id ?? $auth_user?->tech_id,
        ];

        $response = $this->dy_service->getBundleProducts($payload);

        if (!($response['Status'] ?? false) || !empty($response['Error'])) {
            return response()->json([
                'status'  => false,
                'message' => $response['Error'] ?? 'Failed to get bundle products.',
            ], 400);
        }

        return response()->json($response);
    }

    // add bundle products to appointment
    public function addBundleProductsToAppointment(AddBundleProductsToAppointmentRequest $request)
    {
        $validated  = $request->validated();
        $techId     = $validated['tech_id'] ?? auth()->user()?->tech_id;
        $bookId     = $validated['book_id'];
        $logService = app(TechnicianAppointmentLogService::class);

        try {
            // ── 1. Authorize tech ─────────────────────────────────────────
            try {
                $this->checkCompleteService->authorizeTechOrFail(
                    $techId,
                    "Tech ID mismatch detected before updating appointment {$validated['appointment_id']}"
                );
            } catch (\Throwable $e) {
                $logService->unauthorized(
                    techId: $techId,
                    action: 'add_bundle_products',
                    bookId: $bookId,
                    message: 'Unauthorized technician action in addBundleProductsToAppointment',
                    requestPayload: $request->all(),
                    error: $e->getMessage(),
                    userId: auth()->id(),
                    meta: ['appointment_id' => $validated['appointment_id'] ?? null],
                );
                throw $e;
            }

            // ── 2. Get bundle products ────────────────────────────────────
            $bundleProductsResponse = $this->dy_service->getBundleProducts(['itemId' => $validated['bundleId'], 'workerRecId' => $techId]);

            if (
                isset($bundleProductsResponse['Error']) ||
                !isset($bundleProductsResponse['Data']['Products']) ||
                empty($bundleProductsResponse['Data']['Products'])
            ) {
                $logService->failed(
                    techId: $techId,
                    action: 'add_bundle_products',
                    bookId: $bookId,
                    message: 'Bundle not found or has no products',
                    requestPayload: $request->all(),
                    error: $bundleProductsResponse['Error'] ?? 'Empty products response',
                    userId: auth()->id(),
                    meta: ['bundleId' => $validated['bundleId']],
                );

                return response()->json(['message' => 'Bundle not found or has no products.'], 404);
            }

            $products   = $bundleProductsResponse['Data']['Products'];
            $requestQty = (int) $validated['quantity'];
            $bundleName = $products[0]['BundleName'] ?? null;

            // ── 3. Build items ────────────────────────────────────────────
            $items = collect($products)->map(fn($product) => [
                'orderTypeRecId' => (string) $validated['OrderTypeRecId'],
                'ItemNumber'     => $product['ItemNumber'],
                'Quantity'       => (string) ($requestQty * (int) $product['Quantity']),
                'BundleId'          => $validated['bundleId'],
                'WarrantyStatus' => 'None',
                'PaymentMethod'  => 'CASH',
            ])->toArray();

            // ── 4. Call addSalesLine ──────────────────────────────────────
            $payload = [
                '_contract' => [
                    'appointment' => (int) $validated['appointment_id'],
                    'items'       => $items,
                ],
            ];
            // dd($payload);
            Log::info('addBundleProductsToAppointment - Calling addSalesLine with payload', ['payload' => $payload]);

            $response = $this->dy_service->addSalesLine($payload);

            // ── 5. Save to DB ─────────────────────────────────────────────
            $appointmentBundle = DB::transaction(function () use ($validated, $products, $items, $bundleName, $requestQty, $response) {
                $bundle = AppointmentBundle::create([
                    'appointment_id'    => $validated['appointment_id'],
                    'book_id'           => $validated['book_id'],
                    'sales_order_id'    => $validated['sales_order_id'],
                    'bundle_id'         => $validated['bundleId'],
                    'bundle_name'       => $bundleName,
                    'quantity'          => $requestQty,
                    'order_type_rec_id' => (string) $validated['OrderTypeRecId'],
                    'status'            => isset($response['Status']) && $response['Status'] === true ? 'added' : 'failed',
                ]);

                foreach ($products as $product) {
                    $bundle->items()->create([
                        'item_number'       => $product['ItemNumber'],
                        'item_name'         => $product['Name'] ?? null,
                        'quantity'          => $requestQty * (int) $product['Quantity'],
                        'price'             => $product['BundlePrice'] ?? 0,
                        'order_type_rec_id' => (string) $validated['OrderTypeRecId'],
                        'warranty_status'   => 'None',
                        'payment_method'    => 'CASH',
                    ]);
                }

                return $bundle->load('items');
            });

            // ── 6. DY failed ──────────────────────────────────────────────
            if (!isset($response['Status']) || $response['Status'] !== true) {
                $logService->failed(
                    techId: $techId,
                    action: 'add_bundle_products',
                    bookId: $bookId,
                    message: 'addSalesLine DY call failed for bundle',
                    requestPayload: $request->all(),
                    error: $response['Error'] ?? 'Unknown DY error',
                    userId: auth()->id(),
                    meta: [
                        'appointment_id' => $validated['appointment_id'],
                        'bundleId'       => $validated['bundleId'],
                        'bundle_name'    => $bundleName,
                        'dy_response'    => $response,
                    ],
                );

                return response()->json([
                    'message'     => $response['Error'] ?? 'Failed to add bundle products to appointment.',
                    'bundle'      => $appointmentBundle,
                    'dy_response' => $response,
                ], 422);
            }

            // ── 7. Success ────────────────────────────────────────────────
            $logService->success(
                techId: $techId,
                action: 'add_bundle_products',
                bookId: $bookId,
                message: 'Bundle products added successfully',
                requestPayload: $request->all(),
                responsePayload: $response,
                userId: auth()->id(),
                meta: [
                    'appointment_id' => $validated['appointment_id'],
                    'bundleId'       => $validated['bundleId'],
                    'bundle_name'    => $bundleName,
                    'items_count'    => count($items),
                ],
            );

            return response()->json([
                'message' => 'Bundle products added successfully.',
                'bundle'  => $appointmentBundle,
            ]);
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'add_bundle_products',
                bookId: $bookId,
                message: 'addBundleProductsToAppointment failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'appointment_id' => $validated['appointment_id'] ?? null,
                    'bundleId'       => $validated['bundleId'] ?? null,
                    'file'           => $e->getFile(),
                    'line'           => $e->getLine(),
                ],
            );

            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    //update call list score
    public function updateCallListScore(Request $request)
    {
        $validated = $request->validate([
            'callListId' => 'required|string',
            'score' => 'required|numeric|min:0|max:100',
        ]);

        $payload = [
            '_contract' => [
                'TargetId' => $validated['callListId'],
                'TargetScore' => $validated['score'],
            ],
        ];

        $response = $this->dy_service->updateCallListScore($payload);

        if (isset($response['Error'])) {
            return response()->json(['status' => false, 'message' => $response['Error']], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Call list score updated successfully',
            'data' => $response
        ]);
    }
}
