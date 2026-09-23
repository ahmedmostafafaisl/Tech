<?php

namespace App\Http\Controllers\Api;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\DeleteDirectAppointmentRequest;
use App\Http\Requests\Appointment\NewCompleteAppointmentRequest;
use App\Http\Requests\Appointment\SearchAppointmentTransactionSerialRequest;
use App\Http\Requests\Appointment\SendPaymentLinksRequest;
use App\Http\Requests\Appointment\StoreAppointmentAttachmentsRequest;
use App\Http\Requests\Appointment\TodayAppointmentsRequest;
use App\Http\Requests\SalesLine\AddSalesLineRequest;
use App\Http\Requests\SalesLine\DeleteSalesLineRequest;
use App\Http\Requests\SalesLine\UpdateSalesLineRequest;
use App\Http\Requests\Transfer\NewCreateTransferOrderRequest;
use App\Http\Resources\CompleteForm\CompleteFormResource;
use App\Models\AppointmentBundle;
use App\Models\AppointmentFormSubmission;
use App\Models\AppointmentTransaction;
use App\Models\AppointmentTransactionLine;
use App\Models\AppointmentTransactionSerial;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestAdditionalImage;
use App\Models\ChangeRequestImage;
use App\Models\ChangeRequestReason;
use App\Models\CompleteForm;
use App\Models\CompleteIssue;
use App\Models\DirectAppointment;
use App\Models\DirectAppointmentAttachment;
use App\Models\DirectAppointmentLine;
use App\Models\DirectAppointmentPayment;
use App\Models\Setting;
use App\Models\ShortLink;
use App\Models\TechnicianAppointmentLog;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CheckCompleteStatus\CheckCompleteService;
use App\Services\DY365\DyService;
use App\Services\Dynamics\DynamicsAttachmentPayloadService;
use App\Services\Logs\TechnicianAppointmentLogService;
use App\Services\Logs\TechnicianLogService;
use App\Services\Payment\ClickPayService;
use App\Services\Payment\PaymentCompletionDispatcher;
use App\Services\Payment\RequiredAmountCalculator;
use App\Services\Payment\TabbyService;
use App\Services\Payment\TamaraService;
use App\Services\TaqnyatSmsService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PDF;

class NewDirectIntegrationController extends Controller
{
    use ApiResponseHelper;

    protected $dyService;

    protected $tamaraService;

    protected $tabbyService;

    protected $smsService;

    protected $checkCompleteService;

    protected $dynamicsAttachmentPayloadService;

    public function __construct(DyService $dyService, TamaraService $tamaraService, TabbyService $tabbyService, TaqnyatSmsService $smsService, CheckCompleteService $checkCompleteService, DynamicsAttachmentPayloadService $dynamicsAttachmentPayloadService)
    {
        $this->dyService = $dyService;
        $this->tamaraService = $tamaraService;
        $this->tabbyService = $tabbyService;
        $this->smsService = $smsService;
        $this->dynamicsAttachmentPayloadService = $dynamicsAttachmentPayloadService;
        $this->checkCompleteService = $checkCompleteService;
    }

    public function todayAppointments(TodayAppointmentsRequest $request)
    {
        $payload = [
            'worker' => $request->tech_id,
            'currentPage' => $request->currentPage,
            'pageSize' => $request->pageSize,
            'fromDate' => $request->date,
            'toDate' => $request->date,
        ];

        $response = $this->dyService->getTechnicianAppointmentsNew($payload);

        if ($response === null) {
            return response()->json([
                'Status' => false,
                'message' => 'Dynamics is unavailable right now. Please try again.',
            ], 503);
        }

        if (isset($response['Status']) && $response['Status'] === true && isset($response['Data']['Appointments'])) {
            $authenticatedUser = Auth::user();

            $favoriteIds = $authenticatedUser
                ->favoriteAppointments()
                ->pluck('sales_order_id')
                ->toArray();

            // ── Pre-fetch change request book IDs from all appointments ───────────
            $allBookIds = collect($response['Data']['Appointments'])
                ->pluck('BookId')
                ->filter()
                ->unique()
                ->toArray();

            $changeRequestBookIds = ChangeRequest::whereIn('book_id', $allBookIds)
                ->pluck('book_id')
                ->toArray();

            // ── Normalize and add favorite flag ───────────────────────────────────
            $appointments = collect($response['Data']['Appointments'])
                ->map(function ($appointment) use ($favoriteIds) {
                    $appointment = $this->normalizeAppointmentStatus($appointment);

                    $appointment['favorite'] =
                        isset($appointment['SalesOrderId']) &&
                        in_array($appointment['SalesOrderId'], $favoriteIds);

                    return $appointment;
                })
                // ── Exclude Deferral/Canceled if BookId not in change requests ────
                ->filter(function ($appointment) use ($changeRequestBookIds) {
                    $status = $appointment['Status'] ?? null;

                    if (in_array($status, ['Deferral', 'Canceled'], true)) {
                        return in_array($appointment['BookId'] ?? null, $changeRequestBookIds);
                    }

                    return true; // all other statuses pass through
                });

            // ── Filter by status ──────────────────────────────────────────────────
            if ($request->filled('status')) {
                $appointments = $appointments->filter(
                    fn($a) => ($a['Status'] ?? null) === $request->status
                );
            }

            // ── Filter by shift ───────────────────────────────────────────────────
            if ($request->filled('shift')) {
                $appointments = $appointments->filter(
                    fn($a) => ($a['ShiftNameId'] ?? null) === $request->shift
                );
            }

            // ── Sort ──────────────────────────────────────────────────────────────
            $appointments = $appointments
                ->sortBy(fn($item) => strtotime($item['FromTime'] ?? ''))
                ->values();

            // ── Stats AFTER filtering ─────────────────────────────────────────────
            $statusCounts = $appointments->groupBy(fn($a) => $a['Status'] ?? 'unknown')
                ->map(fn($group) => $group->count())
                ->toArray();

            $shiftCounts = $appointments->groupBy(fn($a) => $a['ShiftNameId'] ?? 'unknown')
                ->map(fn($group) => $group->count())
                ->toArray();

            $response['Data']['Appointments'] = $appointments->toArray();
            $response['Data']['StatusCounts'] = $statusCounts;
            $response['Data']['ShiftCounts'] = $shiftCounts;
        }

        return $this->setCode(200)
            ->setData($response)
            ->setMessage('Success.')
            ->send();
    }

    public function singleAppointment2($sales_order_id)
    {
        $authenticatedUser = Auth::user();

        // ✅ 1. Get appointment from Dynamics
        $response = $this->dyService->getAppointmentBySalesOrder($sales_order_id);

        if (
            ! isset($response['Status']) ||
            $response['Status'] !== true ||
            ! isset($response['Data']['SalesLines'])
        ) {
            return $this->setCode(400)->setMessage('Invalid appointment response')->send();
        }

        $appointment = $response['Data'];
        $salesLines = $appointment['SalesLines'] ?? [];

        // ✅ 2. Get warehouse stock
        $stock = $this->newTechStockWarehouse($authenticatedUser->warehouse_id);

        // Convert stock to associative array for fast lookup
        $stockMap = collect($stock)->mapWithKeys(function ($item) {
            return [$item['ItemNumber'] => $item['Quantity']];
        });

        // ✅ 3. Add `max_quantity` to each sales line
        $salesLines = array_map(function ($line) use ($stockMap) {
            $itemNumber = $line['ItemNumber'] ?? null;
            $line['max_quantity'] = $itemNumber && isset($stockMap[$itemNumber])
                ? $stockMap[$itemNumber]
                : 0;

            return $line;
        }, $salesLines);

        // ✅ 4. Attach back updated SalesLines
        $appointment['SalesLines'] = $salesLines;
        // ✅ 5. Check if appointment is in user's favorites
        $isFavorite = $authenticatedUser->favoriteAppointments()
            ->where('sales_order_id', $appointment['SalesOrderId'])
            ->exists();

        // ✅ 6. Add favorite flag to response
        $appointment['favorite'] = $isFavorite;
        // ✅ 7. Get any existing change requests for this appointment
        $changeRequests = ChangeRequest::with('images')
            ->where('sales_order_id', $sales_order_id)
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'request_type' => (int) $request->request_type,
                    'notes' => $request->notes,
                    'created_at' => $request->created_at,
                    'images' => $request->images->map(fn($img) => Storage::disk('s3')->temporaryUrl($img->image, now()->addHours(100))),
                ];
            });

        $appointment['change_requests'] = $changeRequests;
        // 🔹 NEW CONDITION: Completed BUT NO ATTACHMENTS
        if (($appointment['Status'] ?? null) === 'Completed') {

            $salesOrderId = $appointment['SalesOrderId'] ?? null;

            if ($salesOrderId) {
                $direct = DirectAppointment::withCount('attachments')   // <-- count attachments
                    ->where('sales_order_id', $salesOrderId)
                    ->latest('id')
                    ->first();

                // if has 0 attachments → change to in_progress
                if ($direct && $direct->attachments_count == 0) {
                    $appointment['Status'] = 'in_progress';
                }
            }
        }

        // ✅ 8. Return formatted response
        return $this->setCode(200)
            ->setData($appointment)
            ->setMessage('Success.')
            ->send();
    }

    // s3 url for attachments
    private function s3Url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        return Storage::disk('s3')->url($path);
    }

    public function singleAppointmentByBookId($bookId)
    {
        $response = $this->dyService->getAppointmentByBookIdNew($bookId, 0);

        if ($response === null) {
            return response()->json([
                'Status' => false,
                'message' => 'Dynamics is unavailable right now. Please try again.',
            ], 503);
        }

        if (! ($response['Status'] ?? false) || ! isset($response['Data'])) {
            return $this->setCode(400)->setMessage('Invalid appointment response')->send();
        }

        $appointment = $response['Data'];
        $salesLines = $appointment['SalesLines'] ?? [];
        // $rec = User::where('tech_id', $appointment['Worker'])->orderByDesc('id')->first();
        $rec = $this->getTechnicianUser($appointment['Worker']);
        $authenticatedUser = $rec ?? Auth::user();
        // ✅ Fetch stock only for items in salesLines
        $stockMap = $this->buildStockMapForSalesLines($salesLines, $authenticatedUser->warehouse_id);

        // ✅ Appointment bundles by book_id
        $appointmentBundles = AppointmentBundle::query()
            ->with('items')
            ->where('book_id', $bookId)
            ->get();

        $appointment['bundles'] = $appointmentBundles->map(fn(AppointmentBundle $bundle) => [
            'id' => $bundle->id,
            'bundle_id' => $bundle->bundle_id,
            'bundle_name' => $bundle->bundle_name,
            'quantity' => $bundle->quantity,
            'order_type_rec_id' => $bundle->order_type_rec_id,
            'status' => $bundle->status,
            'items' => $bundle->items->map(fn($item) => [
                'id' => $item->id,
                'item_number' => $item->item_number,
                'item_name' => $item->item_name,
                'quantity' => $item->quantity,
            ]),
        ])->values();

        // ✅ Build item_number => { bundle_id, bundle_name } map from DB
        $bundleItemMap = $appointmentBundles->reduce(function ($carry, AppointmentBundle $bundle) {
            foreach ($bundle->items as $item) {
                $carry[strtolower($item->item_number)] = [
                    'bundle_id' => $bundle->bundle_id,
                    'bundle_name' => $bundle->bundle_name,
                ];
            }

            return $carry;
        }, collect());

        // ✅ Map sales lines with max_quantity, serial data, bundle_id and bundle_name
        $salesLines = array_map(function ($line) use ($stockMap, $bundleItemMap) {
            $itemNumber = strtolower($line['ItemNumber'] ?? '');
            $bundleEntry = $bundleItemMap->get($itemNumber);

            $line['max_quantity'] = $stockMap->get($itemNumber)['Quantity'] ?? 0;
            $line = $this->resolveSerialData($line, $stockMap);
            $line['bundle_id'] = $bundleEntry['bundle_id'] ?? null;
            $line['bundle_name'] = $bundleEntry['bundle_name'] ?? null;

            return $line;
        }, $salesLines);
        // ✅ Attach updated SalesLines
        $appointment['SalesLines'] = $salesLines;

        // ✅ Favorite flag
        $appointment['favorite'] = $authenticatedUser->favoriteAppointments()
            ->where('sales_order_id', $appointment['SalesOrderId'])
            ->exists();

        // ✅ Change requests

        $changeRequestsCacheKey = "change_requests:{$bookId}:{$appointment['SalesOrderId']}";

        $appointment['change_requests'] = Cache::remember(
            $changeRequestsCacheKey,
            now()->addHour(),
            function () use ($bookId, $appointment) {
                return ChangeRequest::query()
                    ->where('book_id', $bookId)
                    ->where('sales_order_id', $appointment['SalesOrderId'])
                    ->select(['id', 'request_type', 'reason', 'reason_rec_id', 'notes', 'book_id', 'sales_order_id', 'created_at'])
                    ->with([
                        'images:id,change_request_id,image',
                        'additionalImages:id,change_request_id,image,type',
                    ])
                    ->latest('id')
                    ->get()
                    ->map(fn(ChangeRequest $request) => [
                        'id' => $request->id,
                        'request_type' => $request->request_type == 0 ? 'Cancel' : 'Reschedule',
                        'reason_name' => $request->reason,
                        'reason_rec_id' => $request->reason_rec_id,
                        'notes' => $request->notes,
                        'created_at' => $request->created_at,
                        'images' => $request->images->pluck('image')->map(fn($k) => $this->s3Url($k))->values(),
                        'additional_images' => $request->additionalImages->pluck('image')->map(fn($k) => $this->s3Url($k))->values(),
                    ])
                    ->values();
            }
        );

        // ✅ Normalize status
        $appointment = $this->normalizeAppointmentStatus($appointment);

        // ✅ Invoice sent
        $shortLink = ShortLink::where('book_id', $bookId)->latest('id')->first();
        $appointment['invoice_sent'] = $shortLink ? (bool) $shortLink->sent : false;
        $appointment['invoice_link'] = $shortLink ? $shortLink->url : null;

        // ✅ Direct appointment
        $direct = DirectAppointment::where('book_id', $bookId)->latest('id')->first();
        $appointment['direct_id'] = $direct?->id;

        // ✅ New required_amount calculation — same shared calculator/flag
        // as the rest of the required_amount rework (sendPaymentLinks,
        // completeAppointment, checkPaymentStatus, PaymentCompletionDispatcher,
        // Tabby/Tamara/ClickPay reconciliation). Exposed here as a
        // separate field rather than overwriting RequiredAmount itself,
        // so existing consumers of this response aren't silently affected
        // by a change in what RequiredAmount means.
        if (Setting::isActive('new_required_amount_calculation_active')) {
            $requiredAmount = (float) ($appointment['RequiredAmount'] ?? 0);
            $paidAmount = (float) ($appointment['PaidAmount'] ?? 0);
            $usedBalance = (float) ($appointment['UsedBalance'] ?? 0);

            $appointment['RequiredAmount'] = app(RequiredAmountCalculator::class)
                ->calculate($requiredAmount, $paidAmount, $usedBalance);
        }

        return $this->setCode(200)
            ->setData($appointment)
            ->setMessage('Success.')
            ->send();
    }

    // get sales lines summary for appointment details:
    public function salesLinesSummaryByBookId($bookId)
    {

        $response = $this->dyService->getAppointmentByBookIdNew($bookId, 0);

        if ($response === null) {
            return response()->json([
                'Status' => false,
                'message' => 'Dynamics is unavailable right now. Please try again.',
            ], 503);
        }

        if (! ($response['Status'] ?? false) || ! isset($response['Data'])) {
            return $this->setCode(400)->setMessage('Invalid appointment response')->send();
        }
        // $rec = User::where('tech_id', $response['Data']['Worker'])->orderByDesc('id')->first();
        $rec = $this->getTechnicianUser($response['Data']['Worker']);
        $authenticatedUser = $rec ?? Auth::user();
        $salesLines = $response['Data']['SalesLines'] ?? [];

        $salesLines = array_values(array_filter($salesLines, function ($line) {
            return ($line['OrderTypeId'] ?? null) !== 'خدمات';
        }));
        $stockMap = $this->buildStockMapForSalesLines($salesLines, $authenticatedUser->warehouse_id);

        $zeroStockLines = array_values(array_filter(
            array_map(function ($line) use ($stockMap) {
                $itemNumber = strtolower($line['ItemNumber'] ?? '');
                $maxQuantity = $stockMap->get($itemNumber)['Quantity'] ?? 0;
                $lineQuantity = $line['Quantity'] ?? 0;

                return [
                    'item_number' => $line['ItemNumber'] ?? null,
                    'quantity' => $lineQuantity,
                    'max_quantity' => $maxQuantity,
                ];
            }, $salesLines),
            fn($line) => $line['max_quantity'] === 0 || $line['max_quantity'] < $line['quantity']
        ));

        if (! empty($zeroStockLines)) {
            return $this->setCode(400)
                ->setData($zeroStockLines)
                ->setMessage('Some items have no stock available.')
                ->send();
        }

        return null; // all good
    }

    // check complete eligibility
    public function checkTodayDirectAppointmentsCompleted($bookId): ?JsonResponse
    {
        $authenticatedUser = Auth::user();

        // ✅ Step 1: Get today's paid direct appointments for the auth user
        $todayAppointments = DirectAppointment::query()
            ->with(['attachments', 'submissionForm.values'])
            ->where('tech_id', $authenticatedUser->tech_id)
            ->where('book_id', '!=', $bookId)
            ->where('status', 'paid')
            ->where('dy_attachment_body', null)
            ->whereDate('created_at', today())
            ->get();

        // ✅ Step 2: Check if any appointment has 0 attachments
        $incomplete = $todayAppointments->first(
            fn(DirectAppointment $appointment) => $this->calcNewAttachmentsCount($appointment) === 0
        );

        if ($incomplete) {
            $singleAppointment = $this->refSingleAppointmentByBookId($incomplete->book_id);

            // ✅ null means the external API returned an empty/invalid record — skip
            if ($singleAppointment === null) {
                return null;
            }

            $status = $singleAppointment['Status'] ?? null;

            if ($status === null || in_array($status, ['Deferral', 'Cancelled', 'Completed', 'Canceled'], true)) {
                return null;
            }

            return response()->json([
                'Status' => false,
                'message' => "You must complete the appointment with book id = {$incomplete->book_id}",
            ], 400);
        }

        return null; // ✅ all good
    }

    // get book id change requests
    public function getChangeRequestsByBookId($bookId)
    {
        $changeRequests = ChangeRequest::query()
            ->where('book_id', $bookId)
            ->select(['id', 'book_id', 'sales_order_id', 'request_type', 'reason', 'reason_rec_id', 'notes', 'created_at'])
            ->with([
                'images:id,change_request_id,image',
                'additionalImages:id,change_request_id,image,type',
            ])
            ->latest('id')
            ->get()
            ->map(fn(ChangeRequest $request) => [
                'id' => $request->id,
                'book_id' => $request->book_id,
                'sales_order_id' => $request->sales_order_id,
                'request_type' => $request->request_type == 0 ? 'Cancel' : 'Reschedule',
                'reason_name' => $request->reason,
                'reason_rec_id' => $request->reason_rec_id,
                'notes' => $request->notes,
                'created_at' => $request->created_at,
                'images' => $request->images->pluck('image')->map(fn($k) => $this->s3Url($k))->values(),
                'additional_images' => $request->additionalImages->pluck('image')->map(fn($k) => $this->s3Url($k))->values(),
            ]);

        return $this->setCode(200)
            ->setData($changeRequests)
            ->setMessage('Success.')
            ->send();
    }

    public function buildStockMapForSalesLines(array $salesLines, string $warehouseId): Collection
    {
        $itemNumbers = collect($salesLines)
            ->pluck('ItemNumber')
            ->filter()
            ->unique()
            ->values();

        $allProducts = [];

        foreach ($itemNumbers as $itemNumber) {
            $payload = [
                'warehouseId' => $warehouseId,
                'itemNumber' => '',
                'searchTerm' => $itemNumber,
                'productName' => '',
                'currentPage' => 1,
                'pageSize' => 10,
            ];

            $response = $this->dyService->getWarehouseStockNew($payload, 3);

            if (
                ($response['Status'] ?? false) === true &&
                isset($response['Data']['Products']) &&
                is_array($response['Data']['Products'])
            ) {
                $allProducts = array_merge($allProducts, $response['Data']['Products']);
            }
        }

        return collect($allProducts)->mapWithKeys(
            fn($item) => [strtolower($item['ItemNumber']) => $item]
        );
    }

    private function resolveSerialData(array $line, Collection $stockMap): array
    {
        if (! ($line['IsSerial'] ?? false)) {
            $line['available_serials'] = [];
            $line['selected_serials'] = [];

            return $line;
        }

        $itemNumber = strtolower($line['ItemNumber'] ?? '');
        $salesLineRecId = $line['SaleslineId'] ?? null;
        $stockItem = $stockMap->get($itemNumber);

        // All serials available in stock for this item
        $line['available_serials'] = collect($stockItem['ProductsPerSerial'] ?? [])
            ->map(fn($s) => [
                'serial' => preg_replace('/[\pZ\pC\x{00A0}\x{200B}\x{FEFF}]+/u', '', $s['SerialNum']),
                'quantity' => $s['Quantity'],
            ])
            ->values()
            ->toArray();

        // Serials already selected in DB for this sales line
        $line['selected_serials'] = AppointmentTransactionSerial::where('sales_line_rec_id', $salesLineRecId)
            ->pluck('serial')
            ->values()
            ->toArray();

        return $line;
    }

    private function normalizeAppointmentStatus(array $appointment): array
    {
        $status = $appointment['Status'] ?? null;
        $salesOrderId = $appointment['SalesOrderId'] ?? null;
        $bookId = $appointment['BookId'] ?? null;

        if (! $salesOrderId || ! $status) {
            return $appointment;
        }

        $direct = DirectAppointment::where('book_id', $bookId)
            ->where('status', 'paid')
            ->where('complete_flag', 1)
            ->latest('id')
            ->first();

        $completeExists = $bookId
            ? CompleteForm::where('book_id', $bookId)->exists()
            : false;

        $formSubmissionExists = $bookId
            ? AppointmentFormSubmission::where('book_id', $bookId)->exists()
            : false;

        if (in_array($status, ['Delayed', 'Scheduled'], true)) {
            if ($direct && ($completeExists || $formSubmissionExists)) {
                $appointment['Status'] = 'in_progress';

                return $appointment;
            }
        }

        // if ($status === 'Completed') {
        //     if ($direct && !$completeExists && !$formSubmissionExists) {
        //         $appointment['Status'] = 'in_progress';
        //     }
        // }

        return $appointment;
    }

    public function singleAppointment($sales_order_id)
    {
        $response = $this->dyService->getAppointmentBySalesOrder($sales_order_id);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    public function techWarehouse(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'tech_id' => 'required|integer|exists:users,tech_id',
            'currentPage' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
            'productName' => 'nullable|string',
        ]);
        if ($validated->fails()) {
            return response()->json(['error' => $validated->errors()], 400);
        }

        $payload = [
            'worker' => (int) $request->tech_id,
            'currentPage' => (int) $request->currentPage,
            'pageSize' => (int) $request->pageSize,
            'itemNumber' => (int) $request->itemNumber ?? '',
            'productName' => $request->productName ?? '',

        ];

        $response = $this->dyService->getTechnicianStock($payload);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    public function mainWarehouse(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'warehouse_id' => 'required|string',
            'currentPage' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:100',
            'searchTerm' => 'nullable|string',
            'bookId' => 'nullable|string',
            'workerId' => 'required|string',
        ]);

        if ($validated->fails()) {
            return response()->json(['error' => $validated->errors()], 400);
        }

        $payload = [
            'warehouseId' => $request->warehouse_id,
            'searchTerm' => $request->searchTerm ?? '',
            'currentPage' => (int) $request->currentPage,
            'pageSize' => (int) $request->pageSize,
        ];

        $warehouseResponse = $this->dyService->getWarehouseStockNew($payload, 0);

        if ($warehouseResponse === null) {
            return response()->json([
                'Status' => false,
                'message' => 'Dynamics warehouse stock is unavailable right now. Please try again.',
            ], 503);
        }

        $products = $warehouseResponse['Data']['Products'] ?? [];
        $products = is_array($products) ? $products : [];

        // ── Trim serials (handles non-breaking spaces \u00a0 and regular spaces) ──
        $cleanSerial = fn($s) => preg_replace(
            '/[\pZ\pC\x{00A0}\x{200B}\x{FEFF}]+/u',
            '',
            (string) $s
        );

        $products = collect($products)->map(function ($product) use ($cleanSerial) {
            if (! empty($product['ProductsPerSerial']) && is_array($product['ProductsPerSerial'])) {
                $product['ProductsPerSerial'] = collect($product['ProductsPerSerial'])
                    ->map(fn($s) => array_merge(
                        $s,
                        ['SerialNum' => $cleanSerial($s['SerialNum'] ?? '')]
                    ))
                    ->toArray();
            }

            return $product;
        })->toArray();

        /**
         * Get book_ids that have change requests today
         * These appointments should NOT reserve serials
         */
        $excludedBookIds = ChangeRequest::query()
            ->where('tech_id', $request->workerId)
            ->whereBetween('created_at', [today()->subDay()->startOfDay(), today()->endOfDay()])
            ->pluck('book_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        /**
         * Get reserved serials for this technician today
         * excluding appointments that have change requests today
         */
        $todayReservedSerials = AppointmentTransactionSerial::query()
            ->select(
                'appointment_transaction_serials.item_number',
                'appointment_transaction_serials.serial'
            )
            ->join(
                'appointment_transaction_lines',
                'appointment_transaction_lines.id',
                '=',
                'appointment_transaction_serials.appointment_transaction_line_id'
            )
            ->join(
                'appointment_transactions',
                'appointment_transactions.id',
                '=',
                'appointment_transaction_lines.appointment_transaction_id'
            )
            ->where('appointment_transactions.tech_id', $request->workerId)
            ->whereBetween('appointment_transactions.created_at', [today()->subDay()->startOfDay(), today()->endOfDay()])
            ->when(
                ! empty($excludedBookIds),
                function ($query) use ($excludedBookIds) {
                    $query->whereNotIn(
                        'appointment_transactions.book_id',
                        $excludedBookIds
                    );
                }
            )
            ->get()
            ->groupBy(fn($row) => strtolower(trim($row->item_number)))
            ->map(function ($rows) use ($cleanSerial) {
                return $rows->pluck('serial')
                    ->map(fn($serial) => $cleanSerial($serial))
                    ->toArray();
            })
            ->toArray();

        /**
         * Remove reserved serials from warehouse products
         */
        $products = collect($products)->map(function ($product) use ($todayReservedSerials, $cleanSerial) {

            $itemNumber = strtolower(trim($product['ItemNumber'] ?? ''));

            if (
                isset($todayReservedSerials[$itemNumber]) &&
                ! empty($product['ProductsPerSerial']) &&
                is_array($product['ProductsPerSerial'])
            ) {
                $reservedSerials = $todayReservedSerials[$itemNumber];

                $product['ProductsPerSerial'] = collect($product['ProductsPerSerial'])
                    ->reject(function ($serialRow) use ($reservedSerials, $cleanSerial) {
                        return in_array(
                            $cleanSerial($serialRow['SerialNum'] ?? ''),
                            $reservedSerials,
                            true
                        );
                    })
                    ->values()
                    ->toArray();
            }

            return $product;
        })->toArray();

        // Update response with cleaned and filtered serials
        $warehouseResponse['Data']['Products'] = $products;

        if (empty($request->bookId)) {
            $filteredProducts = $products;
        } else {
            $appointmentResponse = $this->dyService->getAppointmentByBookIdNew($request->bookId, 0);

            if ($appointmentResponse === null) {
                return response()->json([
                    'Status' => false,
                    'message' => 'Dynamics appointment details are unavailable right now. Please try again.',
                ], 503);
            }

            $appointmentData = $appointmentResponse['Data'] ?? [];
            $salesLines = $appointmentData['SalesLines'] ?? [];
            $salesLines = is_array($salesLines) ? $salesLines : [];

            $salesLineItems = collect($salesLines)
                ->pluck('ItemNumber')
                ->filter()
                ->map(fn($v) => strtolower((string) $v))
                ->values()
                ->toArray();

            $services = Cache::remember('services:item_numbers', now()->addMinutes(10), function () {
                return DB::table('services')
                    ->pluck('item_number')
                    ->map(fn($item) => strtolower((string) $item))
                    ->toArray();
            });

            $ignoreItems = array_values(array_unique(array_merge($salesLineItems, $services)));

            $filteredProducts = collect($products)
                ->map(function ($product) {
                    if (isset($product['ItemNumber'])) {
                        $product['ItemNumber'] = strtolower((string) $product['ItemNumber']);
                    }

                    return $product;
                })
                ->reject(function ($product) use ($ignoreItems) {
                    $item = $product['ItemNumber'] ?? null;

                    return $item && in_array($item, $ignoreItems, true);
                })
                ->values()
                ->toArray();

            // Preserve original logic but actually apply filtered products
            $warehouseResponse['Data']['Products'] = $filteredProducts;
        }

        return $this->setCode(200)
            ->setData($warehouseResponse)
            ->setMessage('Success.')
            ->send();
    }

    public function getTechnicianDistributions($workerId)
    {
        $response = $this->dyService->getTechnicianDistributions([
            'worker' => $workerId,
        ]);

        if ($response === null) {
            return response()->json([
                'Status' => false,
                'message' => 'Dynamics is unavailable right now. Please try again.',
                'Data' => [],
            ], 503);
        }

        return response()->json([
            'Status' => true,
            'message' => 'Success',
            'Data' => $response['Data'] ?? [],
        ]);
    }

    public function getTechnicianLimitData(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'workerId' => 'required|string',
            'itemNumber' => 'required|string',
        ]);

        if ($validated->fails()) {
            return [
                'Status' => false,
                'message' => $validated->errors()->first(),
                'Data' => [
                    'Limit' => 0,
                    'OnHand' => 0,
                    'Remain' => 0,
                ],
            ];
        }

        $workerId = $request->workerId;
        $itemNumber = $request->itemNumber;

        $data = [
            'Limit' => 0,
            'OnHand' => 0,
            'Remain' => 0,
            'category' => null,
        ];

        $limitResponse = $this->dyService->getTechnicianProductLimit([
            '_worker' => $workerId,
            '_itemId' => $itemNumber,
        ]);

        if (! empty($limitResponse['Status']) && ! empty($limitResponse['Data'])) {
            $data['Limit'] = $limitResponse['Data']['Limit'] ?? 0;
            $data['OnHand'] = $limitResponse['Data']['OnHand'] ?? 0;
            $data['Remain'] = $limitResponse['Data']['Remain'] ?? 0;
            $data['category'] = $limitResponse['Data']['Category'] ?? null;
        }

        return [
            'Status' => true,
            'message' => 'Success',
            'Data' => $data,
        ];
    }
    // handle sales lines

    private function resolveSalesLineIds(array $items, array $dyLines): array
    {
        $totalItems = count($items);
        $totalDyLines = count($dyLines);

        // ✅ DY returned all items — map by ItemNumber
        if ($totalDyLines >= $totalItems) {
            $mapped = collect($dyLines)->keyBy(fn($l) => strtoupper($l['ItemNumber']));

            return array_map(function ($item) use ($mapped) {
                $key = strtoupper($item['ItemNumber']);
                $dyLine = $mapped->get($key);

                return [
                    'item' => $item,
                    'salesline_id' => $dyLine['SaleslineId'] ?? null,
                ];
            }, $items);
        }

        // ✅ DY returned fewer lines — calculate from last ID
        $lastDyLine = end($dyLines);
        $lastSaleslineId = $lastDyLine['SaleslineId'] ?? null;

        return array_map(function ($item, $index) use ($lastSaleslineId, $totalItems) {
            $saleslineId = $lastSaleslineId
                ? ($lastSaleslineId - ($totalItems - 1 - $index))
                : null;

            return [
                'item' => $item,
                'salesline_id' => $saleslineId,
            ];
        }, $items, array_keys($items));
    }

    // add sales line
    public function addSalesLine(AddSalesLineRequest $request)
    {
        $validatedData = $request->validated();
        $techId = $validatedData['tech_id'] ?? auth()->user()?->tech_id;
        $bookId = $validatedData['book_id'];
        $logService = app(TechnicianAppointmentLogService::class);

        // check if appointment is completed and has attachments
        $checkDirect = DirectAppointment::where('book_id', $bookId)
            ->where('status', 'paid')
            ->where('complete_flag', 1)
            ->latest('id')
            ->first();

        if ($checkDirect) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot add sales line to a completed appointment .',
            ], 400);
        }

        try {
            try {
                $this->checkCompleteService->authorizeTechOrFail(
                    $techId,
                    "Tech ID mismatch detected before updating appointment {$validatedData['appointment']}"
                );
            } catch (\Throwable $e) {
                $logService->unauthorized(
                    techId: $techId,
                    action: 'add_sales_line',
                    bookId: $bookId,
                    message: 'Unauthorized technician action in addSalesLine',
                    requestPayload: $request->all(),
                    error: $e->getMessage(),
                    userId: auth()->id(),
                    meta: ['appointment' => $validatedData['appointment'] ?? null],
                );
                throw $e;
            }

            $payload = [
                '_contract' => [
                    'appointment' => (int) $validatedData['appointment'],
                    'items' => $validatedData['items'],
                ],
            ];

            $response = $this->dyService->addSalesLine($payload);

            // ✅ Save to DB if DY returned success
            if (($response['Status'] ?? false) === true) {
                DB::transaction(function () use ($validatedData, $response, $techId, $bookId) {

                    $transaction = AppointmentTransaction::firstOrCreate(
                        ['book_id' => $bookId],
                        ['rec_id' => $validatedData['appointment'], 'tech_id' => $techId]
                    );

                    $dyLines = $response['Data']['SalesLines'] ?? [];
                    $resolved = $this->resolveSalesLineIds($validatedData['items'], $dyLines);

                    foreach ($resolved as ['item' => $item, 'salesline_id' => $saleslineId]) {
                        $line = AppointmentTransactionLine::firstOrCreate(
                            [
                                'appointment_transaction_id' => $transaction->id,
                                'item_number' => $item['ItemNumber'],
                            ],
                            [
                                'sales_line_rec_id' => $saleslineId,
                                'quantity' => $item['Quantity'],
                                'order_type_rec_id' => $item['orderTypeRecId'],
                                'warranty_status' => $item['WarrantyStatus'] ?? 'None',
                            ]
                        );

                        if ($saleslineId && ! $line->sales_line_rec_id) {
                            $line->update(['sales_line_rec_id' => $saleslineId]);
                        }

                        foreach ($item['serials'] ?? [] as $serial) {
                            AppointmentTransactionSerial::firstOrCreate(
                                ['appointment_transaction_line_id' => $line->id, 'serial' => $serial],
                                ['sales_line_rec_id' => $saleslineId, 'item_number' => $item['ItemNumber']]
                            );
                        }
                    }
                });
            }

            $logService->success(
                techId: $techId,
                action: 'add_sales_line',
                bookId: $bookId,
                message: 'Added a new Sales Line successfully',
                requestPayload: $request->all(),
                responsePayload: $response,
                userId: auth()->id(),
                meta: ['appointment' => $validatedData['appointment']],
            );

            return $this->setCode(200)->setData($response)->setMessage('Success.')->send();
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'add_sales_line',
                bookId: $bookId,
                message: 'addSalesLine failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: ['appointment' => $request->input('appointment'), 'file' => $e->getFile(), 'line' => $e->getLine()],
            );

            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // update sales line
    public function updateSalesLine(UpdateSalesLineRequest $request)
    {
        $data = $request->all();
        $validatedData = $request->validated();
        $techId = $validatedData['tech_id'] ?? auth()->user()?->tech_id;
        $bookId = $validatedData['book_id'];
        $logService = app(TechnicianAppointmentLogService::class);

        // check if appointment is completed and has attachments
        $checkDirect = DirectAppointment::where('book_id', $bookId)
            ->where('status', 'paid')
            ->where('complete_flag', 1)
            ->latest('id')
            ->first();

        if ($checkDirect) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot add sales line to a completed appointment .',
            ], 400);
        }
        // Normalize warranty/payment keys
        if (! empty($data['salesLines']) && is_array($data['salesLines'])) {
            $data['salesLines'] = array_map(function ($line) {
                if (! is_array($line)) {
                    return $line;
                }
                if (array_key_exists('warrantyStatus', $line) && ! array_key_exists('WarrantyStatus', $line)) {
                    $line['WarrantyStatus'] = $line['warrantyStatus'];
                }
                if (array_key_exists('paymentMethod', $line) && ! array_key_exists('PaymentMethod', $line)) {
                    $line['PaymentMethod'] = $line['paymentMethod'];
                }

                return $line;
            }, $data['salesLines']);
        }

        try {
            try {
                $this->checkCompleteService->authorizeTechOrFail(
                    $techId,
                    "Tech ID mismatch detected before updating appointment {$validatedData['appointment']}"
                );
            } catch (\Throwable $e) {
                $logService->unauthorized(
                    techId: $techId,
                    action: 'update_sales_line',
                    bookId: $bookId,
                    message: 'Unauthorized technician action in updateSalesLine',
                    requestPayload: $request->all(),
                    error: $e->getMessage(),
                    userId: auth()->id(),
                    meta: ['appointment' => $validatedData['appointment'] ?? null],
                );
                throw $e;
            }

            // Filter only unpaid lines for external service
            $unpaidLines = array_values(array_filter($validatedData['salesLines'] ?? [], function ($line) {
                return empty($line['IsPaid']) || $line['IsPaid'] === false;
            }));

            $payload = null;

            if (! empty($unpaidLines)) {
                $payload = [
                    '_contract' => [
                        'appointment' => (int) $validatedData['appointment'],
                        'salesLines' => array_map(function ($line) {
                            return array_filter([
                                'salesLineRecId' => (int) $line['salesLineRecId'],
                                'quantity' => (int) $line['quantity'],
                                'WarrantyStatus' => $line['WarrantyStatus'] ?? null,
                                'PaymentMethod' => $line['PaymentMethod'] ?? null,
                            ], fn($v) => $v !== null);
                        }, $unpaidLines),
                    ],
                ];

                $response = $this->dyService->updateSalesLineNew($payload);
            } else {
                // All lines are paid — skip external call
                $response = ['Status' => true, 'Message' => 'All lines are paid, skipped external update.'];
            }

            // ✅ DB transaction runs for ALL lines (paid + unpaid)
            if (($response['Status'] ?? false) === true) {
                DB::transaction(function () use ($validatedData, $techId, $bookId) {

                    $transaction = AppointmentTransaction::firstOrCreate(
                        ['book_id' => $bookId],
                        [
                            'rec_id' => $validatedData['appointment'],
                            'tech_id' => $techId,
                        ]
                    );

                    foreach ($validatedData['salesLines'] as $lineData) {

                        $line = AppointmentTransactionLine::updateOrCreate(
                            [
                                'appointment_transaction_id' => $transaction->id,
                                'sales_line_rec_id' => $lineData['salesLineRecId'],
                            ],
                            [
                                'item_number' => $lineData['itemNumber'] ?? 'unknown',
                                'quantity' => $lineData['quantity'],
                                'order_type_rec_id' => 0,
                                'warranty_status' => $lineData['WarrantyStatus'] ?? 'None',
                            ]
                        );

                        // ✅ Sync serials safely
                        if (isset($lineData['serials'])) {
                            $line->serials()->delete();

                            foreach ($lineData['serials'] as $serial) {
                                AppointmentTransactionSerial::updateOrCreate(
                                    [
                                        'sales_line_rec_id' => $line->sales_line_rec_id,
                                        'serial' => $serial,
                                    ],
                                    [
                                        'appointment_transaction_line_id' => $line->id,
                                        'item_number' => $line->item_number,
                                    ]
                                );
                            }
                        }
                    }
                });
            }

            $logService->success(
                techId: $techId,
                action: 'update_sales_line',
                bookId: $bookId,
                message: 'Updated Sales Line successfully',
                requestPayload: $request->all(),
                responsePayload: $response,
                userId: auth()->id(),
                meta: [
                    'appointment' => $validatedData['appointment'],
                    'dy_payload' => $payload,
                    'skipped_unpaid' => empty($unpaidLines),
                ],
            );

            return $this->setCode(200)->setData($response)->setMessage('Success.')->send();
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'update_sales_line',
                bookId: $bookId,
                message: 'updateSalesLine failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'appointment' => $request->input('appointment'),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // delete sales line
    public function deleteSalesLine(DeleteSalesLineRequest $request)
    {
        $validatedData = $request->validated();
        $techId = $validatedData['tech_id'] ?? auth()->user()?->tech_id;
        $bookId = $validatedData['book_id'];
        $itemNumbers = array_map('strtolower', $validatedData['item_numbers']);
        $logService = app(TechnicianAppointmentLogService::class);

        // check if appointment is completed and has attachments
        $checkDirect = DirectAppointment::where('book_id', $bookId)
            ->where('status', 'paid')
            ->where('complete_flag', 1)
            ->latest('id')
            ->first();

        if ($checkDirect) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot add sales line to a completed appointment .',
            ], 400);
        }

        try {
            // ── 1. Authorize tech ─────────────────────────────────────────
            try {
                $this->checkCompleteService->authorizeTechOrFail(
                    $techId,
                    "Tech ID mismatch detected before updating appointment {$validatedData['appointment']}"
                );
            } catch (\Throwable $e) {
                $logService->unauthorized(
                    techId: $techId,
                    action: 'delete_sales_line',
                    bookId: $bookId,
                    message: 'Unauthorized technician action in deleteSalesLine',
                    requestPayload: $request->all(),
                    error: $e->getMessage(),
                    userId: auth()->id(),
                    meta: ['appointment' => $validatedData['appointment'] ?? null],
                );
                throw $e;
            }

            // ── 2. Check if any deleted item belongs to a bundle ──────────
            $affectedBundles = AppointmentBundle::query()
                ->with('items')
                ->where('book_id', $bookId)
                ->where('status', 'added')
                ->get()
                ->filter(
                    fn(AppointmentBundle $bundle) => $bundle->items->contains(
                        fn($item) => in_array(strtolower($item->item_number), $itemNumbers)
                    )
                );

            // ── 3. Build extra salesLines to delete from bundles ──────────
            // For each affected bundle, collect items NOT in the current delete request
            // so we delete the full bundle from DY too
            $extraBundleItems = collect();

            foreach ($affectedBundles as $bundle) {
                foreach ($bundle->items as $item) {
                    if (! in_array(strtolower($item->item_number), $itemNumbers)) {
                        $extraBundleItems->push($item->item_number);
                    }
                }
            }

            // ── 4. Build DY payload ───────────────────────────────────────
            $payload = [
                '_contract' => [
                    'appointment' => (int) $validatedData['appointment'],
                    'salesLines' => array_map('intval', $validatedData['salesLines']),
                ],
            ];

            $response = $this->dyService->deleteSalesLine($payload);

            $isSuccess = ($response['Status'] ?? false) === true;

            // ── 5. On DY success — clean up DB ────────────────────────────
            if ($isSuccess) {
                DB::transaction(function () use ($validatedData, $bookId, $affectedBundles) {

                    // Delete transaction lines & serials
                    $transaction = AppointmentTransaction::where('book_id', $bookId)->first();

                    if ($transaction) {
                        foreach ($validatedData['salesLines'] as $salesLineRecId) {
                            $line = AppointmentTransactionLine::where([
                                'appointment_transaction_id' => $transaction->id,
                                'sales_line_rec_id' => $salesLineRecId,
                            ])->first();

                            if (! $line) {
                                continue;
                            }

                            $line->serials()->delete();
                            $line->delete();
                        }

                        if ($transaction->lines()->count() === 0) {
                            $transaction->delete();
                        }
                    }

                    // Delete affected bundles and their items from DB
                    foreach ($affectedBundles as $bundle) {
                        $bundle->items()->delete();
                        $bundle->delete();
                    }
                });
            }

            // ⚠ FIXED: previously always logged success() and always
            // returned 200 (via setCode(200)), even when DY365 rejected
            // the delete (e.g. "Item is paid.") — meaning a failed delete
            // looked identical to a successful one to both the caller and
            // the logs. Now logs and returns according to what actually
            // happened.
            if ($isSuccess) {
                $logService->success(
                    techId: $techId,
                    action: 'delete_sales_line',
                    bookId: $bookId,
                    message: 'Deleted Sales Line successfully',
                    requestPayload: $request->all(),
                    responsePayload: $response,
                    userId: auth()->id(),
                    meta: [
                        'appointment' => $validatedData['appointment'],
                        'affected_bundles' => $affectedBundles->pluck('bundle_id')->toArray(),
                        'extra_items_removed' => $extraBundleItems->toArray(),
                    ],
                );

                return $this->setCode(200)->setData($response)->setMessage('Success.')->send();
            }

            $logService->failed(
                techId: $techId,
                action: 'delete_sales_line',
                bookId: $bookId,
                message: 'DY365 rejected delete sales line',
                requestPayload: $request->all(),
                responsePayload: $response,
                userId: auth()->id(),
                meta: [
                    'appointment' => $validatedData['appointment'],
                ],
            );

            $statusCode = (int) ($response['Code'] ?? 400);

            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = 400;
            }

            return response()->json($response, $statusCode);
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'delete_sales_line',
                bookId: $bookId,
                message: 'deleteSalesLine failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'appointment' => $request->input('appointment'),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // create or update invoice  by sales order id
    public function getOrCreateInvoice($sales_order_id)
    {

        $payload = [
            'salesOrderId' => $sales_order_id,
        ];
        $response = $this->dyService->getOrCreateInvoice($payload);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    // create or update invoice  by Book id
    public function getOrCreateInvoiceByBookId($book_id)
    {
        $payload = [
            'bookId' => $book_id,
        ];
        $response = $this->dyService->getOrCreateInvoiceByBookId($payload);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    // create transfer order
    public function createTransferOrder(NewCreateTransferOrderRequest $request)
    {
        $validated = $request->validated();

        $payload = [
            '_contract' => [
                'Type' => $validated['type'],
                'TechnicianPersonnelNumber' => $validated['TechnicianPersonnelNumber'],
                'FromWarehouseId' => $validated['fromWarehouseId'],
                'ToWarehouseId' => $validated['toWarehouseId'],
                'items' => $validated['items'],
            ],
        ];

        $response = $this->dyService->createTransferOrder($payload);

        $techId = $request->input('tech_id') ?? Auth::user()->tech_id;

        app(TechnicianLogService::class)->log(
            $techId,
            'create_transfer_order',
            'Created Transfer Order from ' . ($validated['fromWarehouseId'] ?? '') . ' to ' . ($validated['toWarehouseId'] ?? ''),
            auth()->id() ?? null,
            ['payload' => $payload]
        );

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    // update transfer order status
    public function updateTransferOrderStatus(Request $request)
    {
        $rules = [
            'type' => 'required|string|in:TechnicianToWarehouse,WarehouseToTechnician',
            'transferOrderId' => 'required|string',
            'TechnicianStatus' => 'required|string|in:Confirmed,Rejected',
        ];
        $validated = Validator::make($request->all(), $rules);
        if ($validated->fails()) {
            return response()->json(['error' => $validated->errors()], 400);
        }
        $payload = [
            '_contract' => [
                'TransferId' => $request['transferOrderId'],
                'Type' => $request['type'],
                'TechnicianStatus' => $request['TechnicianStatus'],
            ],
        ];
        $response = $this->dyService->updateTransferOrder($payload);
        // Log the action
        $techId = $request->input('tech_id') ?? Auth::user()->tech_id;
        app(TechnicianLogService::class)->log(
            $techId,
            'update_transfer_order',
            'Updated Transfer Order ' . ($payload['TransferId'] ?? ''),
            auth()->id() ?? null,
            ['payload' => $payload]
        );

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    // delete transfer order
    public function deleteTransferOrder(Request $request)
    {
        $rules = [
            'transferOrderId' => 'required|string',
        ];
        $validated = Validator::make($request->all(), $rules);
        if ($validated->fails()) {
            return response()->json(['error' => $validated->errors()], 400);
        }

        $payload = [
            '_contract' => [
                'TransferId' => $request['transferOrderId'],
            ],
        ];

        $response = $this->dyService->deleteTransferOrder($payload);
        // Log the action
        $techId = $request->input('tech_id') ?? Auth::user()->tech_id;

        app(TechnicianLogService::class)->log(
            $techId,
            'delete_transfer_order',
            'Deleted Transfer Order ' . ($payload['TransferId'] ?? ''),
            auth()->id() ?? null,
            ['payload' => $payload]
        );

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    // get transfer order by technician id
    public function getTransferOrders($tech_id, Request $request)
    {
        $payload = [
            'worker' => (int) $tech_id,
            'currentPage' => $request->input('currentPage', 1),
            'pageSize' => $request->input('pageSize', 30),
            'searchTerm' => $request->input('searchTerm', ''),
        ];
        $response = $this->dyService->getTechnicianTransfers($payload);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    public function sendPaymentLinks(SendPaymentLinksRequest $request)
    {
        $validatedData = $request->validated();
        $sales_order_id = $validatedData['sales_order_id'];
        $book_id = $validatedData['book_id'];
        $discount_value = (float) ($validatedData['discount'] ?? 0);
        $paymentsInput = $validatedData['payments'] ?? [];
        $items = $validatedData['items'] ?? [];
        $total_price = (float) ($validatedData['total_price'] ?? 0);
        $InstallmentStatus = $validatedData['InstallmentStatus'] ?? null;
        $results = [];

        $user = auth()->user();
        $tech_id = $validatedData['tech_id'] ?? $user?->tech_id ?? $user?->technician_rec_id ?? null;
        $requestTechId = $validatedData['tech_id'] ?? null;

        $logService = app(TechnicianAppointmentLogService::class);

        // 0) check complete eligibility and stock for all items before proceeding
        $checkEligibility = $this->checkTodayDirectAppointmentsCompleted($book_id);
        if ($checkEligibility !== null) {
            return $checkEligibility;
        }
        $stockCheck = $this->salesLinesSummaryByBookId($book_id);
        if ($stockCheck !== null) {
            return $stockCheck; // returns the 400 error response
        }

        // 0b) Cooldown: don't send to Dynamics until 15 minutes have
        // passed since the technician's previous appointment was sent —
        // unless the most recent one IS this same appointment.
        $cooldownRemaining = $this->getRemainingCooldownMinutes($tech_id, $book_id);
        if (!(is_null($cooldownRemaining) || $cooldownRemaining === 0)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please wait before sending another appointment to Dynamics.',
                'timer'   => $cooldownRemaining,
            ], 429);
        }

        // 1) authorize technician
        try {
            if (! $user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated',
                ], 401);
            }

            try {
                $this->checkCompleteService->authorizeTechOrFail_new(
                    $requestTechId,
                    "Tech ID mismatch detected before updating appointment {$book_id}"
                );
            } catch (HttpResponseException $e) {
                $logService->unauthorized(
                    techId: $tech_id,
                    action: 'send_payment_links',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'Unauthorized technician action in sendPaymentLinks',
                    requestPayload: $request->all(),
                    error: 'Unauthorized',
                    userId: auth()->id(),
                    meta: [
                        'response_status' => 403,
                        'request_tech_id' => $requestTechId,
                        'auth_tech_id' => $tech_id,
                    ],
                );

                throw $e;
            }

            // 2) save appointment transaction items first
            if (! empty($items)) {
                $this->saveAppointmentTransactionItems($book_id, $tech_id, $items);
            }

            // 3) get fresh appointment data
            $singleAppointment = $this->refSingleAppointmentByBookId($book_id);

            if (! $singleAppointment || ! isset($singleAppointment['required_amount']) || $singleAppointment['required_amount'] === 'not found') {
                $logService->failed(
                    techId: $tech_id,
                    action: 'send_payment_links',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'Required amount not found for this Book ID.',
                    requestPayload: $request->all(),
                    userId: auth()->id(),
                );

                return response()->json([
                    'status' => false,
                    'message' => 'Required amount not found for this Book ID.',
                ], 404);
            }

            $required_amount = (float) $singleAppointment['required_amount'];
            $lines = $singleAppointment['sales_lines'] ?? [];

            // ✅ New required_amount calculation (Step 1 of the
            // required_amount rework) — feature-flagged so it can be
            // toggled without a code revert. When OFF, $required_amount
            // stays exactly as it was before this change (the raw DY365
            // value). When ON, it becomes:
            //   required_amount - PaidAmount, then minus used_balance
            //   (only if still > 0), never negative.
            // Single source of truth: RequiredAmountCalculator — do not
            // duplicate this formula elsewhere; reuse this same class.
            if (Setting::isActive('new_required_amount_calculation_active')) {
                $paidAmount = (float) ($singleAppointment['PaidAmount'] ?? 0);
                $usedBalance = (float) ($singleAppointment['used_balance'] ?? 0);

                $required_amount = app(RequiredAmountCalculator::class)
                    ->calculate($required_amount, $paidAmount, $usedBalance);
            }

            // ✅ order_type تركيب/منتجات — TotalAmountSum < 500 vs >= 500
            // (real DY365 data, not the client-submitted items):
            //   - < 500 (and not tech-visit-only): sales_lines MUST
            //     include a delivery fee line (fes-transportation).
            //   - >= 500: sales_lines must NOT include fes-transportation.
            $orderTypeId = $singleAppointment['OrderTypeId'] ?? null;
            $totalAmountSum = (float) ($singleAppointment['TotalAmountSum'] ?? 0);
            $salesLinesForCheck = collect($lines);

            if (in_array($orderTypeId, ['تركيب', 'منتجات'], true)) {
                $hasDeliveryFee = $salesLinesForCheck->contains(
                    fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'fes-transportation'
                );

                if ($totalAmountSum < 500) {
                    $isTechVisitOnly = $salesLinesForCheck->contains(
                        fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'fes-tech-visit'
                    );

                    if (! $isTechVisitOnly && ! $hasDeliveryFee) {
                        $logService->validationFailed(
                            techId: $tech_id,
                            action: 'send_payment_links',
                            bookId: $book_id,
                            salesOrderId: $sales_order_id,
                            message: 'Missing required delivery fee line for low-value تركيب/منتجات appointment',
                            requestPayload: $request->all(),
                            responsePayload: [
                                'order_type_id' => $orderTypeId,
                                'total_amount_sum' => $totalAmountSum,
                            ],
                            userId: auth()->id(),
                        );

                        return response()->json([
                            'status' => false,
                            'message' => 'total_sum_validation_lower_than_500_missing_delivery_fee(fes-transportation)',
                        ], 400);
                    }
                } else {
                    if ($hasDeliveryFee) {
                        $logService->validationFailed(
                            techId: $tech_id,
                            action: 'send_payment_links',
                            bookId: $book_id,
                            salesOrderId: $sales_order_id,
                            message: 'Unexpected delivery fee line for a تركيب/منتجات appointment that does not qualify for it',
                            requestPayload: $request->all(),
                            responsePayload: [
                                'order_type_id' => $orderTypeId,
                                'total_amount_sum' => $totalAmountSum,
                            ],
                            userId: auth()->id(),
                        );

                        return response()->json([
                            'status' => false,
                            'message' => 'total_sum_validation_500_or_more_unexpected_delivery_fee(fes-transportation)',
                        ], 400);
                    }
                }
            }

            // ✅ naqi-s00004 must be kept entirely separate — if present,
            // it cannot be combined with any other products, regardless
            // of order_type or anything else. Checked against real
            // DY365 sales_lines, not client-submitted items.
            $hasNaqiS00004 = $salesLinesForCheck->contains(
                fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'naqi-s00004'
            );

            if ($hasNaqiS00004 && $salesLinesForCheck->count() !== 1) {
                $logService->validationFailed(
                    techId: $tech_id,
                    action: 'send_payment_links',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'naqi-s00004 must be kept separate from other products',
                    requestPayload: $request->all(),
                    responsePayload: [
                        'sales_lines_count' => $salesLinesForCheck->count(),
                    ],
                    userId: auth()->id(),
                );

                return response()->json([
                    'status' => false,
                    'message' => 'item_exclusivity_validation(naqi-s00004)',
                ], 400);
            }

            // 4) validate sales lines before continuing
            $validation = $this->validateSalesLinesBeforeComplete($book_id, $lines);

            if (! $validation['valid']) {
                $logService->failed(
                    techId: $tech_id,
                    action: 'send_payment_links',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'SalesLines validation failed before sending payment links',
                    requestPayload: $request->all(),
                    responsePayload: $validation['errors'],
                    userId: auth()->id(),
                );

                return response()->json([
                    'status' => false,
                    'message' => 'SalesLines validation failed.',
                    'errors' => $validation['errors'],
                ], 400);
            }

            return DB::transaction(function () use ($sales_order_id, $book_id, $tech_id, $discount_value, $paymentsInput, $required_amount, $lines, &$results, $request, $logService, $items, $InstallmentStatus) {
                $directAppointment = DirectAppointment::firstOrCreate(
                    ['book_id' => $book_id],
                    [
                        'customer_phone' => $request->input('customer_phone') ?? null,
                        'order_type' => $request->input('order_type') ?? null,
                        'sales_order_id' => $sales_order_id,
                        'tech_id' => $tech_id,
                        'total_price' => (float) $request->input('total_price'),
                        'discount' => $discount_value,
                        'complete_flag' => 0,
                        'required_amount' => $required_amount,
                        'collect' => max(0, $required_amount - $discount_value),
                        'status' => 'pending',
                        'installment_status' => $InstallmentStatus,
                    ]
                );

                if (! $directAppointment->sales_order_id) {
                    $directAppointment->sales_order_id = $sales_order_id;
                    $directAppointment->save();
                }

                $paid_total = (float) $directAppointment->payments()
                    ->where('status', 'paid')
                    ->sum('price');

                if ($paid_total <= 0.00001) {
                    $directAppointment->discount = $discount_value;
                    $directAppointment->save();
                }

                $final_discount = (float) ($directAppointment->discount ?? 0);

                if ((float) ($directAppointment->required_amount ?? 0) !== (float) $required_amount) {
                    $directAppointment->required_amount = $required_amount;
                    $directAppointment->save();
                }

                $new_payments_total = (float) collect($paymentsInput)->sum('price');
                $calculated_total = $final_discount + $paid_total + $new_payments_total;

                if (abs($calculated_total - $required_amount) > 0.01) {
                    $directAppointment->update([
                        'collect' => max(0, $required_amount - $paid_total - $final_discount),
                        'total_price' => max(0, $required_amount - $final_discount),
                        'required_amount' => $required_amount,
                        'status' => 'pending',
                    ]);

                    $responseBody = [
                        'status' => false,
                        'message' => 'The total of payments (new + paid) plus discount must equal the required amount.',
                        'details' => [
                            'required_amount' => $required_amount,
                            'discount' => $final_discount,
                            'new_payments' => $new_payments_total,
                            'already_paid' => $paid_total,
                            'calculated' => $calculated_total,
                        ],
                    ];

                    $logService->failed(
                        techId: $tech_id,
                        action: 'send_payment_links',
                        bookId: $book_id,
                        salesOrderId: $sales_order_id,
                        message: 'Payments plus discount do not match required amount.',
                        requestPayload: $request->all(),
                        responsePayload: $responseBody,
                        userId: auth()->id(),
                        meta: [
                            'appointment_id' => $directAppointment->id,
                        ],
                    );

                    return response()->json($responseBody, 400);
                }

                // 5) save sales lines once if not existing
                if (! empty($lines)) {
                    $existingLines = DirectAppointmentLine::where('direct_appointment_id', $directAppointment->id)->exists();

                    if (! $existingLines) {

                        $maxQuantityMap = collect($items)
                            ->keyBy(fn($item) => strtolower($item['ItemNumber'] ?? ''))
                            ->map(fn($item) => $item['max_quantity'] ?? null);

                        foreach ($lines as $line) {
                            $line = (array) $line;
                            DirectAppointmentLine::create([
                                'direct_appointment_id' => $directAppointment->id,
                                'sales_order_id' => $sales_order_id,
                                'SaleslineId' => $line['SaleslineId'] ?? null,
                                'ProductRecId' => $line['ProductRecId'] ?? null,
                                'ItemNumber' => $line['ItemNumber'] ?? null,
                                'ProductName' => $line['ProductName'] ?? null,
                                'OrderTypeRecId' => $line['OrderTypeRecId'] ?? null,
                                'OrderTypeId' => $line['OrderTypeId'] ?? null,
                                'IsPaid' => $line['IsPaid'] ?? false,
                                'Quantity' => $line['Quantity'] ?? 1,
                                'UnitPrice' => $line['UnitPrice'] ?? 0,
                                'TotalAmount' => $line['TotalAmount'] ?? 0,
                                'Discount' => $line['Discount'] ?? 0,
                                'ItemIdCommonIssue' => $line['ItemIdCommonIssue'] ?? null,
                                'DescriptionCommonIssue' => $line['DescriptionCommonIssue'] ?? null,
                                'SalesHistoryDate' => isset($line['SalesHistoryDate'])
                                    ? Carbon::parse($line['SalesHistoryDate'])
                                    : null,
                                'WarrantyStatus' => $line['WarrantyStatus'] ?? null,
                                'PaymentMethodRecId' => $line['PaymentMethodRecId'] ?? null,
                                'PaymentMethod' => $line['PaymentMethod'] ?? null,
                                'PaymentReference' => $line['PaymentReference'] ?? null,
                                'max_quantity' => $maxQuantityMap->get(strtolower($line['ItemNumber'] ?? '')),

                            ]);
                        }
                    }
                }

                foreach ($paymentsInput as $paymentData) {
                    $price = (float) ($paymentData['price'] ?? 0);
                    $phone = (string) ($paymentData['phone'] ?? '');
                    $isPaid = (bool) ($paymentData['is_paid'] ?? false);
                    $inputRef = $paymentData['reference_id'] ?? null;

                    $rawType = (string) ($paymentData['payment_type'] ?? '');
                    $typeUpper = strtoupper(trim($rawType));

                    $paymentType = match ($typeUpper) {
                        'TABBY', 'TABI' => 'TABI',
                        'TAMARA' => 'TAMARA',
                        'E-COMMERCE', 'ECOMMERCE', 'E-COMMERCE ' => 'E-Commerce',
                        'CASH' => 'CASH',
                        'POS' => 'POS',
                        'TRNS' => 'TRNS',
                        default => $typeUpper,
                    };

                    if ($inputRef) {
                        $reference_id = $inputRef;
                    } else {
                        do {
                            $reference_id = strtoupper(Str::random(12));
                        } while (DirectAppointmentPayment::where('reference_id', $reference_id)->exists());
                    }

                    $forcePaid = in_array($paymentType, ['CASH', 'POS', 'TRNS'], true);
                    $status = ($isPaid || $forcePaid) ? 'paid' : 'pending';

                    if ($status === 'paid' && in_array($paymentType, ['TABI', 'TAMARA', 'E-Commerce'], true) && $isPaid) {
                        $checkDuplicate = $this->checkCompleteService->checkDuplicatePayment(
                            $sales_order_id,
                            $paymentType,
                            $price,
                            $reference_id
                        );

                        if ($checkDuplicate instanceof JsonResponse) {
                            $duplicateBody = $checkDuplicate->getData(true);

                            $logService->failed(
                                techId: $tech_id,
                                action: 'send_payment_links',
                                bookId: $book_id,
                                salesOrderId: $sales_order_id,
                                message: 'Duplicate payment detected',
                                requestPayload: $request->all(),
                                responsePayload: $duplicateBody,
                                userId: auth()->id(),
                                meta: [
                                    'reference_id' => $reference_id,
                                    'payment_type' => $paymentType,
                                    'price' => $price,
                                ],
                            );

                            return $checkDuplicate;
                        }
                    }

                    $payment = DirectAppointmentPayment::create([
                        'direct_appointment_id' => $directAppointment->id,
                        'sales_order_id' => $sales_order_id,
                        'book_id' => $book_id,
                        'price' => $price,
                        'status' => $status,
                        'payment_type' => $paymentType,
                        'reference_id' => $reference_id,
                        'phone' => $phone,
                    ]);

                    if ($status === 'pending') {
                        $link = null;

                        if ($paymentType === 'TABI') {
                            $tabbyResponse = app(TabbyService::class)->checkoutNew($payment, $price, $phone, $sales_order_id);
                            $tabbyData = $tabbyResponse->getData(true);
                            $link = $tabbyData['web_url'] ?? null;
                        } elseif ($paymentType === 'TAMARA') {
                            app(TamaraService::class)->pre_checkout($phone, $price);
                            $tamara = app(TamaraService::class)->createOrderNew($payment, $price, $phone, $sales_order_id);
                            $link = $tamara['checkout_url'] ?? null;
                        } elseif ($paymentType === 'E-Commerce') {
                            $clickpay = app(ClickPayService::class)->createInvoiceNew($payment, $price, $phone, $sales_order_id);
                            $payment->payment_id = $clickpay['reference_id'] ?? null;
                            $payment->reference_id = $reference_id;
                            $payment->save();
                            $link = $clickpay['redirect_url'] ?? null;
                        }

                        if ($link) {

                            app(TaqnyatSmsService::class)->sendPaymentLink($phone, $link);

                            $results[] = [
                                'reference_id' => $payment->reference_id,
                                'payment_url' => $link,
                            ];
                        }
                    }
                }

                $paidSum = (float) $directAppointment->payments()
                    ->where('status', 'paid')
                    ->sum('price');

                $final_discount = (float) ($directAppointment->discount ?? 0);
                $required = (float) ($directAppointment->required_amount ?? 0);

                $collect = max(0, $required - $final_discount - $paidSum);
                $allPaid = abs(($paidSum + $final_discount) - $required) < 0.01;

                $directAppointment->update([
                    'collect' => $collect,
                    'status' => $allPaid ? 'paid' : 'pending',
                    'installment_status' => $directAppointment->installment_status ?? null,
                ]);

                $dispatchResult = null;

                $responseBody = [
                    'status' => true,
                    'message' => 'Payment links processed successfully',
                    'data' => $results,
                    'meta' => [
                        'appointment_id' => $directAppointment->id,
                        'required_amount' => $required,
                        'paid_sum' => $paidSum,
                        'discount' => $final_discount,
                        'collect' => $collect,
                        'installment_status' => $directAppointment->installment_status ?? null,
                        'status_final' => $allPaid ? 'paid' : 'pending',
                    ],
                ];

                $logService->success(
                    techId: $tech_id,
                    action: 'send_payment_links',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'Payment links processed successfully',
                    requestPayload: $request->all(),
                    responsePayload: $responseBody,
                    userId: auth()->id(),
                    meta: [
                        'appointment_id' => $directAppointment->id,
                        'required_amount' => $required,
                        'paid_sum' => $paidSum,
                        'discount' => $final_discount,
                        'collect' => $collect,
                        'installment_status' => $directAppointment->installment_status ?? null,
                        'status_final' => $allPaid ? 'paid' : 'pending',
                        'dispatch_result' => $dispatchResult,
                    ],
                );

                return response()->json($responseBody);
            });
        } catch (HttpResponseException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('sendPaymentLinks failed', [
                'error' => $e->getMessage(),
                'book_id' => $book_id,
                'sales_order_id' => $sales_order_id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $logService->failed(
                techId: $tech_id,
                action: 'send_payment_links',
                bookId: $book_id,
                salesOrderId: $sales_order_id,
                message: 'sendPaymentLinks failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage() ?: 'Internal server error',
            ], 500);
        }
    }

    public function checkPaymentStatus(Request $request)
    {
        $sales_order_id = $request->input('sales_order_id');
        $book_id = $request->input('book_id');
        $reference_id = $request->input('reference_id');
        $reference_ids = $request->input('reference_ids'); // array

        // 1) Check payment status by sales_order_id / book_id
        if ($sales_order_id || $book_id) {

            if (empty($book_id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'book_id is required',
                ], 422);
            }

            $singleAppointment = $this->refSingleAppointmentByBookId($book_id);
            $required_amount = (float) ($singleAppointment['required_amount'] ?? 0);

            // ✅ New required_amount calculation (Step 3 — same shared
            // calculator/flag as sendPaymentLinks/completeAppointment).
            // Matters here because this value drives both which query
            // branch runs below (paid vs free) AND what gets written back
            // to collect/total_price/required_amount/status — an
            // appointment already settled via PaidAmount/used_balance
            // should be treated the same as required_amount == 0.
            if (Setting::isActive('new_required_amount_calculation_active')) {
                $paidAmount = (float) ($singleAppointment['PaidAmount'] ?? 0);
                $usedBalance = (float) ($singleAppointment['used_balance'] ?? 0);

                $required_amount = app(RequiredAmountCalculator::class)
                    ->calculate($required_amount, $paidAmount, $usedBalance);
            }

            // ✅ use first version logic, but replace only the payment query
            if ($required_amount > 0) {
                $payment = DirectAppointment::query()
                    ->withCount('attachments')
                    ->with('completeForm')
                    ->withSum(['payments as paid_total' => function ($q) {
                        $q->where('status', 'paid');
                    }], 'price')
                    ->withCount(['payments as paid_payments_count' => function ($q) {
                        $q->where('status', 'paid');
                    }])
                    ->where('book_id', $book_id)
                    ->whereHas('payments', function ($q) {
                        $q->where('status', 'paid');
                    })
                    ->orderByDesc('id')
                    ->first();
            } else {
                $payment = DirectAppointment::query()
                    ->withCount('attachments')
                    ->with('completeForm')
                    ->withSum(['payments as paid_total' => function ($q) {
                        $q->where('status', 'paid');
                    }], 'price')
                    ->withCount(['payments as paid_payments_count' => function ($q) {
                        $q->where('status', 'paid');
                    }])
                    ->where(function ($q) use ($book_id, $sales_order_id) {
                        $q->where('book_id', $book_id);

                        if (! empty($sales_order_id)) {
                            $q->orWhere('sales_order_id', $sales_order_id);
                        }
                    })
                    ->orderByDesc('id')
                    ->first();
            }

            if ($payment) {

                $paid_total = (float) ($payment->paid_total ?? 0);
                $db_discount = (float) ($payment->discount ?? 0);

                // total = paid + discount (حسب منطقك)
                $calculated_total = $db_discount + $paid_total;

                // update collect/total_price/required_amount
                $payment->update([
                    'collect' => max(0, $required_amount - $calculated_total),
                    'total_price' => max(0, $required_amount - $db_discount),
                    'required_amount' => $required_amount,
                ]);

                // status update
                if ($required_amount > 0 && abs($calculated_total - $required_amount) < 0.01) {
                    $payment->update(['status' => 'paid', 'collect' => 0]);
                } else {
                    if ($payment->status !== 'paid') {
                        $payment->update(['status' => 'pending']);
                    }
                }

                $newAttachmentsCount = $this->calcNewAttachmentsCount($payment);
                // $timer = $this->resolveSalesLineTimer($book_id);
                $remaining = $this->getRemainingCooldownMinutes();

                return response()->json([
                    'status' => $payment->status,
                    'complete_flag' => (int) $payment->complete_flag,
                    'discount' => (float) $payment->discount,
                    'total_price' => (float) $payment->total_price,
                    'collect' => (float) $payment->collect,
                    'attachments_count' => $newAttachmentsCount,
                    'payments_count' => (int) ($payment->paid_payments_count ?? 0),
                    // 'timer'             => null,
                    'timer' => $remaining,
                ]);
            }
        }

        // 2) Check payment status by reference_id
        if ($reference_id) {
            $p = DirectAppointmentPayment::where('reference_id', $reference_id)
                ->orderByDesc('id')
                ->first();

            if ($p) {
                return response()->json([
                    'payment_type' => $p->payment_type,
                    'status' => $p->status,
                    'reference_id' => $p->reference_id,
                    'price' => (float) $p->price,
                    'created_at' => $p->created_at,
                ]);
            }
        }

        // 3) Check multiple reference ids
        if (is_array($reference_ids) && count($reference_ids) > 0) {
            $payments = DirectAppointmentPayment::whereIn('reference_id', $reference_ids)
                ->orderByDesc('id')
                ->get(['reference_id', 'status', 'price', 'payment_type', 'created_at']);

            if ($payments->isNotEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'count' => $payments->count(),
                    'payments' => $payments,
                ]);
            }
        }
        // $timer = $this->resolveSalesLineTimer($book_id);
        $remaining = $this->getRemainingCooldownMinutes();

        return response()->json(['status' => 'not_found', 'timer' => $remaining]);
    }

    public function getRemainingCooldownMinutes(?int $techId = null, ?string $excludeBookId = null): ?int
    {
        $techId = $techId ?? auth()->user()?->tech_id;

        if (!$techId) {
            return null;
        }

        $query = DirectAppointment::where('tech_id', $techId)
            ->where('complete_v2_calling', 'like', 'done:%');

        // "or until the number registered in the database is used" — if
        // the technician's most recent completed appointment IS the
        // current one being processed, don't let it count against
        // itself; look for the next most recent genuinely different one.
        if ($excludeBookId) {
            $query->where('book_id', '!=', $excludeBookId);
        }

        $lastAppointment = $query->latest('updated_at')->first();

        if (! $lastAppointment) {
            return null;
        }

        $doneAt = Carbon::parse(
            str_replace('done:', '', $lastAppointment->complete_v2_calling)
        );

        $cooldownMinutes = (int) Setting::get('appointment_cooldown_minutes', 15);

        $secondsSinceDone = $doneAt->diffInSeconds(now());
        $totalSeconds = ($cooldownMinutes * 60) - $secondsSinceDone;

        return $totalSeconds > 0 ? $totalSeconds : null;
    }

    private function resolveSalesLineTimer(?string $bookId): ?int
    {
        if (! $bookId) {
            return null;
        }

        $lastLog = TechnicianAppointmentLog::query()
            ->where('book_id', $bookId)
            ->where('status', 'success')
            ->whereIn('action', [
                'delete_sales_line',
                'update_sales_line',
                'add_sales_line',
            ])
            ->latest('created_at')
            ->first();

        if (! $lastLog) {
            return null;
        }

        $diffInSeconds = Carbon::now()->diffInSeconds($lastLog->created_at);

        if ($diffInSeconds > 180) {
            return null;
        }

        return 180 - $diffInSeconds;
    }

    public function calcNewAttachmentsCount(DirectAppointment $appointment): int
    {
        // old attachments table count (from relation)
        $oldCount = (int) ($appointment->attachments_count ?? $appointment->attachments()->count());

        // count non-null complete form images
        $form = CompleteForm::query()
            ->where('book_id', $appointment->book_id)
            ->latest()
            ->first();

        $submissionForm = $appointment->relationLoaded('submissionForm')
            ? $appointment->submissionForm
            : $appointment->submissionForm()->with('values')->first();

        if ($submissionForm) {

            $jsonCount = $submissionForm->values
                ->filter(fn($value) => is_array($value->value_json)) // بس اللي فيه array
                ->sum(fn($value) => count($value->value_json));      // عد الصور جوه كل array

            $oldCount += $jsonCount;
        }

        $completeFormCount = 0;

        if ($form) {
            $fields = [
                'home_salt_image',
                'device_salt_image',
                'carbon_depletion_image',
                'sink_cleaning_image',
                'drain_connection_image',
                'additional_image',
            ];

            foreach ($fields as $f) {
                if (! empty($form->{$f})) {
                    $completeFormCount++;
                }
            }
        }

        return $oldCount + $completeFormCount;
    }

    public function newCheckPaymentStatus(Request $request)
    {
        // ✅ Normalize input to always be arrays (handle both single string and array)
        $salesOrderInput = $request->input('sales_order_id');
        $referenceInput = $request->input('reference_id');

        $salesOrderIds = is_array($salesOrderInput)
            ? $salesOrderInput
            : (empty($salesOrderInput) ? [] : [$salesOrderInput]);

        $referenceIds = is_array($referenceInput)
            ? $referenceInput
            : (empty($referenceInput) ? [] : [$referenceInput]);

        $results = [];

        // ✅ 1. Check payments by sales_order_id
        if (! empty($salesOrderIds)) {
            $salesPayments = DirectAppointment::whereIn('sales_order_id', $salesOrderIds)
                ->orderByDesc('id')
                ->get();

            foreach ($salesPayments as $payment) {
                $results[] = [
                    'sales_order_id' => $payment->sales_order_id,
                    'status' => $payment->status,
                    'complete_flag' => $payment->complete_flag,
                    'discount' => $payment->discount,
                    'total_price' => $payment->total_price,
                    'source' => 'sales_order',
                ];
            }
        }

        // ✅ 2. Check payments by reference_id
        if (! empty($referenceIds)) {
            $referencePayments = DirectAppointmentPayment::whereIn('reference_id', $referenceIds)
                ->orderByDesc('id')
                ->get();

            foreach ($referencePayments as $payment) {
                $results[] = [
                    'reference_id' => $payment->reference_id,
                    'status' => $payment->status,
                    'price' => $payment->price,
                    'source' => 'reference',
                ];
            }
        }

        // ✅ 3. Handle no results
        if (empty($results)) {
            return response()->json([
                'status' => 'not_found',
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => true,
            'count' => count($results),
            'data' => $results,
        ]);
    }

    public function completeAppointment(NewCompleteAppointmentRequest $request)
    {
        $validatedData = $request->validated();
        $sales_order_id = $validatedData['sales_order_id'];
        $book_id = $validatedData['book_id'];
        $discount_value = (float) ($validatedData['discount'] ?? 0);
        $tech_id = $validatedData['tech_id'] ?? auth()->user()?->tech_id;
        $total_price = (float) ($validatedData['total_price'] ?? 0);
        $InstallmentStatus = $validatedData['InstallmentStatus'] ?? null;
        $items = $validatedData['items'] ?? [];

        $logService = app(TechnicianAppointmentLogService::class);

        // 0) check complete eligibility and stock for all items before proceeding

        $checkEligibility = $this->checkTodayDirectAppointmentsCompleted($book_id);
        if ($checkEligibility !== null) {
            return $checkEligibility;
        }

        $stockCheck = $this->salesLinesSummaryByBookId($book_id);
        if ($stockCheck !== null) {
            return $stockCheck; // returns the 400 error response
        }

        // 0b) Cooldown: same rule as sendPaymentLinks() — don't send to
        // Dynamics until 15 minutes have passed since the technician's
        // previous appointment, unless the most recent one IS this same
        // appointment.
        $cooldownRemaining = $this->getRemainingCooldownMinutes($tech_id, $book_id);
        if (!(is_null($cooldownRemaining) || $cooldownRemaining === 0)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please wait before sending another appointment to Dynamics.',
                'timer'   => $cooldownRemaining,
            ], 429);
        }

        try {
            // ✅ Authorize technician
            try {
                $this->checkCompleteService->authorizeTechOrFail(
                    $tech_id,
                    "Tech ID mismatch detected before completing appointment {$book_id}"
                );
            } catch (\Throwable $e) {
                $logService->unauthorized(
                    techId: $tech_id,
                    action: 'complete_appointment',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'Unauthorized technician action in completeAppointment',
                    requestPayload: $request->all(),
                    error: $e->getMessage(),
                    userId: auth()->id(),
                    meta: ['file' => $e->getFile(), 'line' => $e->getLine()],
                );
                throw $e;
            }

            // ✅ Save items to AppointmentTransaction DB first
            if (! empty($items)) {
                $this->saveAppointmentTransactionItems($book_id, $tech_id, $items);
            }

            // ✅ Get single appointment (after saving — selected_serials will be fresh)
            $singleAppointment = $this->refSingleAppointmentByBookId($book_id);

            $required_amount = $singleAppointment['required_amount'] ?? 'not found';

            if ($required_amount === 'not found') {
                $logService->failed(
                    techId: $tech_id,
                    action: 'complete_appointment',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'Unable to retrieve required amount for this Book ID.',
                    requestPayload: $request->all(),
                    userId: auth()->id(),
                );

                return response()->json(['error' => 'Unable to retrieve required amount for this Book ID.'], 400);
            }

            $required_amount = (float) $required_amount;

            // ✅ New required_amount calculation (Step 2 — same shared
            // calculator/flag as sendPaymentLinks). This matters
            // specifically here because this endpoint only proceeds for
            // required_amount == 0 — without this, an appointment already
            // fully settled via PaidAmount/used_balance in DY365 (but
            // whose raw required_amount field is still nonzero) would be
            // permanently rejected by the "Free appointments only" gate
            // below, even though nothing is actually still owed.
            if (Setting::isActive('new_required_amount_calculation_active')) {
                $paidAmount = (float) ($singleAppointment['PaidAmount'] ?? 0);
                $usedBalance = (float) ($singleAppointment['used_balance'] ?? 0);

                $required_amount = app(RequiredAmountCalculator::class)
                    ->calculate($required_amount, $paidAmount, $usedBalance);
            }

            // ✅ order_type تركيب/منتجات + TotalAmountSum < 500 →
            // sales_lines (real DY365 data, not the client-submitted
            // items) must include a delivery fee line (fes-transportation) —
            // UNLESS this is a tech-visit-only appointment (fes-tech-visit
            // already present), since nothing is actually being delivered
            // in that case.
            $orderTypeId = $singleAppointment['OrderTypeId'] ?? null;
            $totalAmountSum = (float) ($singleAppointment['TotalAmountSum'] ?? 0);
            $salesLinesForCheck = collect($singleAppointment['sales_lines'] ?? []);

            if (in_array($orderTypeId, ['تركيب', 'منتجات'], true)) {
                $hasDeliveryFee = $salesLinesForCheck->contains(
                    fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'fes-transportation'
                );

                if ($totalAmountSum < 500) {
                    $isTechVisitOnly = $salesLinesForCheck->contains(
                        fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'fes-tech-visit'
                    );

                    if (! $isTechVisitOnly && ! $hasDeliveryFee) {
                        $logService->validationFailed(
                            techId: $tech_id,
                            action: 'send_payment_links',
                            bookId: $book_id,
                            salesOrderId: $sales_order_id,
                            message: 'Missing required delivery fee line for low-value تركيب/منتجات appointment',
                            requestPayload: $request->all(),
                            responsePayload: [
                                'order_type_id' => $orderTypeId,
                                'total_amount_sum' => $totalAmountSum,
                            ],
                            userId: auth()->id(),
                        );

                        return response()->json([
                            'status' => false,
                            'message' => 'total_sum_validation_lower_than_500_missing_delivery_fee(fes-transportation)',
                        ], 400);
                    }
                } else {
                    if ($hasDeliveryFee) {
                        $logService->validationFailed(
                            techId: $tech_id,
                            action: 'send_payment_links',
                            bookId: $book_id,
                            salesOrderId: $sales_order_id,
                            message: 'Unexpected delivery fee line for a تركيب/منتجات appointment that does not qualify for it',
                            requestPayload: $request->all(),
                            responsePayload: [
                                'order_type_id' => $orderTypeId,
                                'total_amount_sum' => $totalAmountSum,
                            ],
                            userId: auth()->id(),
                        );

                        return response()->json([
                            'status' => false,
                            'message' => 'total_sum_validation_500_or_more_unexpected_delivery_fee(fes-transportation)',
                        ], 400);
                    }
                }
            }

            // ✅ naqi-s00004 must be kept entirely separate — if present,
            // it cannot be combined with any other products, regardless
            // of order_type or anything else. Checked against real
            // DY365 sales_lines, not client-submitted items.
            $hasNaqiS00004 = $salesLinesForCheck->contains(
                fn($line) => strtolower(trim($line['ItemNumber'] ?? '')) === 'naqi-s00004'
            );

            if ($hasNaqiS00004 && $salesLinesForCheck->count() !== 1) {
                $logService->validationFailed(
                    techId: $tech_id,
                    action: 'complete_appointment',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'naqi-s00004 must be kept separate from other products',
                    requestPayload: $request->all(),
                    responsePayload: [
                        'sales_lines_count' => $salesLinesForCheck->count(),
                    ],
                    userId: auth()->id(),
                );

                return response()->json([
                    'status' => false,
                    'message' => 'item_exclusivity_validation(naqi-s00004)',
                ], 400);
            }

            // ✅ Free appointments only
            if ($required_amount > 0) {
                $logService->failed(
                    techId: $tech_id,
                    action: 'complete_appointment',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'This appointment requires payment.',
                    requestPayload: $request->all(),
                    responsePayload: ['required_amount' => $required_amount],
                    userId: auth()->id(),
                );

                return response()->json(['error' => 'This appointment requires payment.'], 400);
            }

            if ($total_price > 0) {
                $logService->validationFailed(
                    techId: $tech_id,
                    action: 'complete_appointment',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'total_price must be 0 for free completion',
                    requestPayload: $request->all(),
                    userId: auth()->id(),
                );

                return response()->json(['error' => 'total_price must be 0 for free completion'], 400);
            }

            // ✅ Save items to AppointmentTransaction DB
            $this->saveAppointmentTransactionItems($book_id, $tech_id, $items);

            // ✅ Validate SalesLines after saving
            $salesLines = $singleAppointment['sales_lines'] ?? [];
            $validation = $this->validateSalesLinesBeforeComplete($book_id, $salesLines);

            if (! $validation['valid']) {
                $logService->failed(
                    techId: $tech_id,
                    action: 'complete_appointment',
                    bookId: $book_id,
                    salesOrderId: $sales_order_id,
                    message: 'SalesLines validation failed before completion',
                    requestPayload: $request->all(),
                    responsePayload: $validation['errors'],
                    userId: auth()->id(),
                );

                return response()->json([
                    'status' => false,
                    'message' => 'SalesLines validation failed.',
                    'errors' => $validation['errors'],
                ], 400);
            }

            // ✅ Create or update direct appointment
            $directAppointment = DirectAppointment::firstOrCreate(
                ['book_id' => $book_id],
                [
                    'customer_phone' => $request->input('customer_phone') ?? null,
                    'order_type' => $request->input('order_type') ?? null,
                    'sales_order_id' => $sales_order_id,
                    'tech_id' => $tech_id,
                    'total_price' => 0,
                    'discount' => $discount_value,
                    'required_amount' => 0,
                    'collect' => 0,
                    'complete_flag' => 0,
                    'status' => 'pending',
                    'installment_status' => $InstallmentStatus,
                ]
            );

            $directAppointment->update([
                'sales_order_id' => $sales_order_id,
                'tech_id' => $tech_id,
                'discount' => $directAppointment->discount ?? $discount_value,
                'required_amount' => 0,
                'collect' => 0,
                'total_price' => 0,
                'status' => 'paid',
                'installment_status' => $InstallmentStatus,
            ]);

            // ✅ Save sales lines with max_quantity
            if (! empty($salesLines)) {
                $existingLines = DirectAppointmentLine::where('direct_appointment_id', $directAppointment->id)->exists();

                if (! $existingLines) {

                    // Build ItemNumber => max_quantity map from request items
                    $maxQuantityMap = collect($items)
                        ->keyBy(fn($item) => strtolower($item['ItemNumber'] ?? ''))
                        ->map(fn($item) => $item['max_quantity'] ?? null);

                    foreach ($salesLines as $line) {
                        $line = (array) $line;
                        $itemNumber = strtolower($line['ItemNumber'] ?? '');

                        DirectAppointmentLine::create([
                            'direct_appointment_id' => $directAppointment->id,
                            'sales_order_id' => $sales_order_id,
                            'SaleslineId' => $line['SaleslineId'] ?? null,
                            'ProductRecId' => $line['ProductRecId'] ?? null,
                            'ItemNumber' => $line['ItemNumber'] ?? null,
                            'ProductName' => $line['ProductName'] ?? null,
                            'OrderTypeRecId' => $line['OrderTypeRecId'] ?? null,
                            'OrderTypeId' => $line['OrderTypeId'] ?? null,
                            'IsPaid' => $line['IsPaid'] ?? false,
                            'Quantity' => $line['Quantity'] ?? 1,
                            'UnitPrice' => $line['UnitPrice'] ?? 0,
                            'TotalAmount' => $line['TotalAmount'] ?? 0,
                            'Discount' => $line['Discount'] ?? 0,
                            'ItemIdCommonIssue' => $line['ItemIdCommonIssue'] ?? null,
                            'DescriptionCommonIssue' => $line['DescriptionCommonIssue'] ?? null,
                            'SalesHistoryDate' => isset($line['SalesHistoryDate'])
                                ? Carbon::parse($line['SalesHistoryDate'])
                                : null,
                            'WarrantyStatus' => $line['WarrantyStatus'] ?? null,
                            'PaymentMethodRecId' => $line['PaymentMethodRecId'] ?? null,
                            'PaymentMethod' => $line['PaymentMethod'] ?? null,
                            'PaymentReference' => $line['PaymentReference'] ?? null,
                            'max_quantity' => $maxQuantityMap->get($itemNumber),
                        ]);
                    }
                }
            }

            $directAppointment->installment_status = $directAppointment->installment_status ?? null;
            $directAppointment->save();
            $responseBody = ['status' => 'success'];

            $logService->success(
                techId: $tech_id,
                action: 'complete_appointment',
                bookId: $book_id,
                salesOrderId: $sales_order_id,
                message: 'Appointment completed successfully',
                requestPayload: $request->all(),
                responsePayload: $responseBody,
                userId: auth()->id(),
                meta: [
                    'appointment_id' => $directAppointment->id,
                    'discount_value' => $directAppointment->discount ?? 0,
                    'total_price' => 0,
                    'dispatch_result' => null,
                ],
            );

            return response()->json($responseBody);
        } catch (\Throwable $e) {
            Log::error('completeAppointment failed', [
                'error' => $e->getMessage(),
                'book_id' => $book_id,
                'sales_order_id' => $sales_order_id,
            ]);

            $logService->failed(
                techId: $tech_id,
                action: 'complete_appointment',
                bookId: $book_id,
                salesOrderId: $sales_order_id,
                message: 'completeAppointment failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: ['file' => $e->getFile(), 'line' => $e->getLine()],
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function saveAppointmentTransactionItems(string $bookId, int $techId, array $items): void
    {
        DB::transaction(function () use ($bookId, $techId, $items) {

            $transaction = AppointmentTransaction::firstOrCreate(
                ['book_id' => $bookId],
                ['rec_id' => 0, 'tech_id' => $techId]
            );

            foreach ($items as $item) {
                $salesLineRecId = $item['SaleslineId'];
                $itemNumber = $item['ItemNumber'];

                // ✅ Single atomic operation — no duplicate key risk
                $line = AppointmentTransactionLine::updateOrCreate(
                    [
                        'appointment_transaction_id' => $transaction->id,
                        'sales_line_rec_id' => $salesLineRecId,
                    ],
                    [
                        'item_number' => $itemNumber,
                        'quantity' => $item['Quantity'],
                        'order_type_rec_id' => $item['orderTypeRecId'],
                        'warranty_status' => $item['WarrantyStatus'] ?? 'None',
                    ]
                );

                // ✅ Sync serials — delete old and insert new
                if (! empty($item['serials'])) {
                    $line->serials()->delete();

                    foreach ($item['serials'] as $serial) {
                        AppointmentTransactionSerial::create([
                            'appointment_transaction_line_id' => $line->id,
                            'sales_line_rec_id' => $salesLineRecId,
                            'item_number' => $itemNumber,
                            'serial' => $serial,
                        ]);
                    }
                }
            }
        });
    }

    private function validateSalesLinesBeforeComplete(string $bookId, array $salesLines): array
    {
        $errors = [];

        $transaction = AppointmentTransaction::where('book_id', $bookId)->first();

        if (! $transaction) {
            return [
                'valid' => false,
                'errors' => ["No transaction record found for book_id: {$bookId}."],
            ];
        }

        foreach ($salesLines as $line) {
            $itemNumber = $line['ItemNumber'] ?? null;
            $salesLineRecId = $line['SaleslineId'] ?? null;
            $isSerial = (bool) ($line['IsSerial'] ?? false);
            $quantity = (int) ($line['Quantity'] ?? 0);

            // ✅ Check 1: Line exists in DB
            $dbLine = AppointmentTransactionLine::where([
                'appointment_transaction_id' => $transaction->id,
                'item_number' => $itemNumber,
                'sales_line_rec_id' => $salesLineRecId,
            ])->first();

            if (! $dbLine) {
                $errors[] = "Item [{$itemNumber}] with SaleslineId [{$salesLineRecId}] not found in transaction records.";

                continue;
            }

            if (! $isSerial) {
                continue;
            }

            // ✅ Use selected_serials from refSingleAppointmentByBookId (no extra DB query)
            $selectedSerials = $line['selected_serials'] ?? [];
            $cleanString = fn($s) => trim(preg_replace('/[\pZ\pC\x{00A0}\x{200B}\x{FEFF}]+/u', '', $s));

            $availableSerials = collect($line['available_serials'] ?? [])
                ->pluck('serial')
                ->map($cleanString)
                ->toArray();

            // ✅ Check 2: Serial count = quantity
            if (count($selectedSerials) !== $quantity) {
                $errors[] = "Item [{$itemNumber}]: expected {$quantity} serial(s), found " . count($selectedSerials) . '.';
            }

            // ✅ Check 3: Every selected serial exists in available stock
            foreach ($selectedSerials as $serial) {
                if (! in_array($cleanString($serial), $availableSerials, true)) {
                    $errors[] = "Item [{$itemNumber}]: serial [{$serial}] is not in available stock. Available serials: " . json_encode($availableSerials);
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    // get invoice by appointment
    public function getInvoiceByAppointment($sales_order_id)
    {
        $body = [
            'salesOrderId' => $sales_order_id,
        ];
        $response = $this->dyService->getOrCreateInvoice($body);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    // apply discount to appointment
    public function applyDiscountToAppointment(Request $request)
    {
        $sales_order_id = $request->input('sales_order_id');
        $discount_value = $request->input('discount', 0);

        if (! $sales_order_id) {
            return response()->json(['error' => 'sales_order_id is required'], 400);
        }

        $payload = [
            '_contract' => [
                'SalesOrderId' => $sales_order_id,
                'Discount' => $discount_value,
            ],
        ];

        $response = $this->dyService->applyDiscountToAppointment($payload);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    // get single technician

    public function getSingleTechnician($tech_id)
    {
        $payload = [
            'worker' => $tech_id,
        ];
        try {
            // 1️⃣ Call the external API
            $response = $this->dyService->getSingleTechnician($payload);

            // 2️⃣ Validate response format
            if (! $response || ! isset($response['Status']) || $response['Status'] !== true || ! isset($response['Data'])) {
                return response()->json(['status' => false, 'message' => 'Invalid API response'], 400);
            }

            $tech = $response['Data'];

            // 3️⃣ Create or update User
            $user = User::updateOrCreate(
                [
                    'technician_rec_id' => $tech['TechnicianRecId'],
                    'type' => 'tech',
                ],
                [
                    'warehouse_id' => $tech['WarehouseId'],
                    'personnel_number' => $tech['PersonnelNumber'],
                    'tech_id' => $tech['TechnicianRecId'],
                    'username' => $tech['Username'],
                    'email' => $tech['Email'] ?? null,
                    'phone' => $tech['Phone'] ?? null,
                    'pin_code' => bcrypt($tech['PINCode'] ?? '0000'),
                    'type' => 'tech',
                    'image' => $tech['Image'] ?? null,
                    'password' => bcrypt($tech['Password'] ?? '00000000'),
                    'status' => $tech['Status'] ?? 'Inactive',
                ]
            );

            // 4️⃣ Handle Main Warehouses — now also capturing IsPrimary per
            // warehouse, keyed by warehouse.id for the sync() pivot call below.
            $warehousePivotData = [];

            if (! empty($tech['MainWarehouses']) && is_array($tech['MainWarehouses'])) {
                foreach ($tech['MainWarehouses'] as $mainWarehouse) {
                    if (! empty($mainWarehouse['MainWarehouseId'])) {
                        $warehouse = Warehouse::firstOrCreate(
                            ['invent_location_id' => $mainWarehouse['MainWarehouseId']],
                            [
                                'name' => $mainWarehouse['MainWarehouseId'],
                                'type' => 'MainWarehouse',
                            ]
                        );

                        $warehousePivotData[$warehouse->id] = [
                            'is_primary' => (bool) ($mainWarehouse['IsPrimary'] ?? false),
                        ];
                    }
                }
            }

            // 5️⃣ Sync warehouses WITH pivot data (replaces old links,
            // including their is_primary values). sync() with an empty
            // array correctly detaches everything, matching what should
            // happen if a technician now has zero MainWarehouses.
            $user->warehouses()->sync($warehousePivotData);

            return response()->json([
                'status' => true,
                'message' => 'Technician synced successfully',
                'data' => $user->load('warehouses'),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to sync technician',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // get single transfer order
    public function getSingleTransferOrder($tech_id, $transfer_order_id)
    {
        $payload = [
            'worker' => $tech_id,
            'transferId' => $transfer_order_id,
        ];
        $response = $this->dyService->getSingleTransferOrder($payload);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    public function generateInvoicePdf($sales_order_id)
    {
        try {
            $payload = ['salesOrderId' => $sales_order_id];
            $response = $this->dyService->getOrCreateInvoice($payload);

            if (! isset($response->Status) || ! $response->Status) {
                return response()->json(['error' => 'Failed to get invoice data'], 400);
            }

            $invoice = $response->Data;

            $pdf = PDF::loadView('invoices.naqi', compact('invoice'))
                ->setOption('defaultFont', 'dejavusans');

            return $pdf->stream('Invoice-' . $invoice->InvoiceId . '.pdf');
        } catch (Exception $e) {
            // 👇 This will show the actual mPDF error
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    // get single direct appointment by sales order id
    public function getSingleDirectAppointment($sales_order_id)
    {
        $direct = DirectAppointment::where('sales_order_id', $sales_order_id)->with('payments', 'lines', 'completeForm', 'submissionForm', 'attachments')
            ->orderBy('id', 'desc')
            ->get();

        if ($direct->isEmpty()) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }

        return response()->json(['data' => $direct], 200);
    }

    // get single direct appointment by sales book id
    public function getSingleDirectAppointment2($book_id)
    {
        $direct = DirectAppointment::where('book_id', $book_id)->with('payments', 'lines', 'submissionForm', 'attachments')
            ->orderBy('id', 'desc')
            ->get();

        if ($direct->isEmpty()) {
            return response()->json(['error' => 'Appointment not found'], 404);
        }

        $complete_form = CompleteForm::where('book_id', $book_id)
            ->orderBy('id', 'desc')
            ->first();

        $direct[0]->completeForm = $complete_form
            ? new CompleteFormResource($complete_form)
            : null;

        return response()->json(['data' => $direct], 200);
    }

    // get single direct appointment by book id
    public function getSingleDirectAppointmentPayments($book_id)
    {
        $direct = DirectAppointment::where('book_id', $book_id)->with('payments', 'lines', 'completeForm', 'submissionForm', 'attachments')
            ->orderBy('id', 'desc')
            ->first();

        if (! $direct) {
            return response()->json(['error' => 'Appointment not found'], 200);
        }

        return response()->json(['data' => $direct], 200);
    }

    // tech appointment change request
    public function submitChangeRequest(Request $request)
    {
        $logService = app(TechnicianAppointmentLogService::class);

        $requestPayloadForLog = $request->except([
            'images',
            'call_images',
            'chat_images',
            'additional_images',
        ]);

        try {
            $validated = Validator::make($request->all(), [
                'sales_order_id' => 'required|string',
                'bookId' => 'required',
                'tech_id' => 'nullable|integer',
                'requestType' => 'required|boolean|in:0,1',
                'notes' => 'required|string|max:2000',
                'reasonRecId' => 'required|integer',
                'reason' => 'nullable|string|max:1000',

                'images' => 'nullable|array',
                'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

                'call_images' => 'nullable|array',
                'call_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

                'chat_images' => 'nullable|array',
                'chat_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

                'additional_images' => 'nullable|array',
                'additional_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ]);

            $techId = $request->input('tech_id') ?? auth()->user()?->tech_id;
            $bookId = $request->input('bookId');

            if ($validated->fails()) {
                $logService->validationFailed(
                    techId: $techId,
                    action: 'submit_change_request',
                    bookId: $bookId,
                    salesOrderId: $request->input('sales_order_id'),
                    message: 'Validation failed in submitChangeRequest',
                    requestPayload: $requestPayloadForLog,
                    responsePayload: $validated->errors()->toArray(),
                    userId: auth()->id(),
                );

                return response()->json(['error' => $validated->errors()], 400);
            }

            $data = $validated->validated();

            try {
                $this->checkCompleteService->authorizeTechOrFail(
                    $data['tech_id'] ?? $techId,
                    "Tech ID mismatch detected before updating appointment {$data['bookId']}"
                );
            } catch (\Throwable $e) {
                $logService->unauthorized(
                    techId: $techId,
                    action: 'submit_change_request',
                    bookId: $bookId,
                    salesOrderId: $data['sales_order_id'] ?? null,
                    message: 'Unauthorized technician action in submitChangeRequest',
                    requestPayload: $requestPayloadForLog,
                    error: $e->getMessage(),
                    userId: auth()->id(),
                    meta: [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ],
                );

                throw $e;
            }

            // ✅ Everything below writes to the DB — only commit if DY365 accepts
            // the request; roll back (nothing persisted) if it doesn't.
            DB::beginTransaction();

            $changeRequest = ChangeRequest::create([
                'sales_order_id' => $data['sales_order_id'],
                'book_id' => $data['bookId'],
                'tech_id' => $techId,
                'request_type' => $data['requestType'],
                'notes' => $data['notes'] ?? null,
                'reason_rec_id' => $data['reasonRecId'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);

            $attachmentUrls = [];

            $attachmentUrls = array_merge(
                $attachmentUrls,
                $this->storeImagesToModel(
                    $request,
                    'images',
                    $data['sales_order_id'],
                    $changeRequest->id,
                    ChangeRequestImage::class,
                    null
                )
            );

            $attachmentUrls = array_merge(
                $attachmentUrls,
                $this->storeImagesToModel(
                    $request,
                    'call_images',
                    $data['sales_order_id'],
                    $changeRequest->id,
                    ChangeRequestAdditionalImage::class,
                    'call'
                )
            );

            $attachmentUrls = array_merge(
                $attachmentUrls,
                $this->storeImagesToModel(
                    $request,
                    'chat_images',
                    $data['sales_order_id'],
                    $changeRequest->id,
                    ChangeRequestAdditionalImage::class,
                    'chat'
                )
            );

            $attachmentUrls = array_merge(
                $attachmentUrls,
                $this->storeImagesToModel(
                    $request,
                    'additional_images',
                    $data['sales_order_id'],
                    $changeRequest->id,
                    ChangeRequestAdditionalImage::class,
                    'additional'
                )
            );

            $payload = [
                '_contract' => [
                    'bookId' => $data['bookId'],
                    'salesOrderId' => $data['sales_order_id'],
                    'requestType' => (int) $data['requestType'],
                    'actionOwner' => 1,
                    'reasonRecId' => $data['reasonRecId'] ?? null,
                    'technicianChangeReqNote' => $data['notes'] ?? $data['reason'] ?? null,
                    'attachmnetsURLs' => array_values(array_unique($attachmentUrls)),
                ],
            ];

            $response = $this->dyService->submitCustomerChangeRequest($payload);

            // Matches your success payload shape:
            // {"$id":"1","Status":true,"Data":"Appointment is updated successfully.","Error":null,"Code":200}
            $isFailed =
                (is_array($response) && (
                    (($response['Status'] ?? null) === false) ||
                    (($response['status'] ?? null) === false) ||
                    (isset($response['Code']) && (int) $response['Code'] >= 400)
                ));

            if ($isFailed) {
                // ❌ DY365 declined the request — discard everything we staged above.
                DB::rollBack();

                $logService->failed(
                    techId: $techId,
                    action: 'submit_change_request',
                    bookId: $bookId,
                    salesOrderId: $data['sales_order_id'],
                    message: 'Change request submission failed',
                    requestPayload: $requestPayloadForLog,
                    responsePayload: $response,
                    userId: auth()->id(),
                    meta: [
                        'attachments_count' => count($attachmentUrls),
                    ],
                );

                // TODO (optional): if you need full consistency with the filesystem,
                // delete the files written by storeImagesToModel() here, since
                // rolling back the DB transaction does not remove uploaded files.

                return $this->setCode(code: $response['Code'] ?? 400)
                    ->setData($response)
                    ->setMessage($response['Error'] ?? 'Change request was rejected.')
                    ->send();
            }

            // ✅ DY365 accepted — persist for real.
            DB::commit();

            // Invalidate the cached change_requests list for this appointment
            // so the next read reflects this newly created request immediately,
            // instead of waiting out the cache TTL.
            Cache::forget("change_requests:{$data['bookId']}:{$data['sales_order_id']}");

            $logService->success(
                techId: $techId,
                action: 'submit_change_request',
                bookId: $bookId,
                salesOrderId: $data['sales_order_id'],
                message: 'Change request submitted successfully',
                requestPayload: $requestPayloadForLog,
                responsePayload: $response,
                userId: auth()->id(),
                meta: [
                    'change_request_id' => $changeRequest->id,
                    'attachments_count' => count($attachmentUrls),
                ],
            );

            return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $logService->failed(
                techId: $request->input('tech_id') ?? auth()->user()?->tech_id,
                action: 'submit_change_request',
                bookId: $request->input('bookId'),
                salesOrderId: $request->input('sales_order_id'),
                message: 'submitChangeRequest failed',
                requestPayload: $requestPayloadForLog,
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function storeImagesToModel(Request $request, string $inputKey, string $salesOrderId, int $changeRequestId, string $modelClass, ?string $type = null): array
    {
        $urls = [];

        if (! $request->hasFile($inputKey)) {
            return $urls;
        }

        foreach ((array) $request->file($inputKey) as $image) {

            if (! $image instanceof UploadedFile || ! $image->isValid()) {
                Log::warning('Invalid image upload', [
                    'sales_order_id' => $salesOrderId,
                    'key' => $inputKey,
                ]);

                continue;
            }

            $extension = $image->getClientOriginalExtension();
            $fileName = uniqid('attachment_', true) . '.' . $extension;
            $folder = "attachments/{$salesOrderId}";

            // ✅ Upload to S3 (NO visibility / NO ACL)
            $path = $image->storeAs($folder, $fileName, 's3');

            if (! $path) {
                Log::warning('S3 storeAs returned false', [
                    'sales_order_id' => $salesOrderId,
                    'key' => $inputKey,
                    'file' => $fileName,
                ]);

                continue;
            }

            $payload = [
                'change_request_id' => $changeRequestId,
                'image' => $path,
            ];

            if (! is_null($type)) {
                $payload['type'] = $type; // requires column type in this table/model
            }

            $modelClass::create($payload);

            $publicUrl = Storage::disk('s3')->url($path);
            if (! empty($publicUrl)) {
                $urls[] = $publicUrl;
            }
        }

        return $urls;
    }

    // get tech appointment change request
    public function getChangeRequests($tech_id)
    {
        $payload = [
            'worker' => $tech_id,
        ];

        $response = $this->dyService->getTechnicianChangeStatusRequests($payload);

        return $this->setCode(code: 200)->setData($response)->setMessage('Success.')->send();
    }

    public function fixAppointments(Request $request)
    {
        $request->validate([
            'scheduleAppointments' => 'nullable|array',
            'discountedAppointments' => 'nullable|array',
            'totalPriceAppointments' => 'nullable|array',
            'paymentUpdates' => 'nullable|array',
            'paymentStatusUpdates' => 'nullable|array',
        ]);

        $scheduleAppointments = $request->input('scheduleAppointments', []);
        $discountedAppointments = $request->input('discountedAppointments', []);
        $totalPriceAppointments = $request->input('totalPriceAppointments', []);
        $paymentUpdates = $request->input('paymentUpdates', []);
        $paymentStatusUpdates = $request->input('paymentStatusUpdates', []);

        DB::beginTransaction();

        try {
            /* -------------------------------------------------------
         * 🟡 1) DELETE Appointments & Payments for Scheduled Orders
         * ------------------------------------------------------- */
            if (! empty($scheduleAppointments)) {

                // get IDs of appointments to delete
                $directIds = DirectAppointment::whereIn('sales_order_id', $scheduleAppointments)
                    ->pluck('id')
                    ->toArray();

                if (! empty($directIds)) {
                    DirectAppointmentPayment::whereIn('direct_appointment_id', $directIds)->delete();
                    DirectAppointment::whereIn('id', $directIds)->delete();
                }
            }

            /* -------------------------------------------------------
         * 🟢 2) UPDATE Discount for Discounted Appointments
         * ------------------------------------------------------- */
            foreach ($discountedAppointments as $salesOrder => $discount) {
                DirectAppointment::where('sales_order_id', $salesOrder)
                    ->latest('id')
                    ->first()
                    ?->update(['discount' => $discount]);
            }

            /* -------------------------------------------------------
         * 🔵 3) UPDATE Total Price for Adjusted Appointments
         * ------------------------------------------------------- */
            foreach ($totalPriceAppointments as $salesOrder => $totalPrice) {
                DirectAppointment::where('sales_order_id', $salesOrder)
                    ->latest('id')
                    ->first()
                    ?->update(['total_price' => $totalPrice]);
            }

            /* -------------------------------------------------------
 * 🔴 4) UPDATE FIRST PAID PAYMENT PRICE
 * ------------------------------------------------------- */
            foreach ($paymentUpdates as $salesOrder => $newPrice) {

                // Get latest appointment for this sales order
                $appointment = DirectAppointment::where('sales_order_id', $salesOrder)
                    ->latest('id')
                    ->first();

                if (! $appointment) {
                    continue;
                }

                // Get FIRST paid payment record
                $payment = DirectAppointmentPayment::where('direct_appointment_id', $appointment->id)
                    ->where('status', 'paid') // adjust column name if different
                    ->orderBy('id', 'asc')            // first payment
                    ->first();

                if ($payment) {
                    $payment->update([
                        'price' => $newPrice,
                    ]);
                }
            }
            /* -------------------------------------------------------
 * 🔴 5) UPDATE FIRST PAID PAYMENT STATUS
 * ------------------------------------------------------- */
            foreach ($paymentStatusUpdates as $salesOrder => $newStatus) {

                // Get latest appointment for this sales order
                $appointment = DirectAppointment::where('sales_order_id', $salesOrder)
                    ->latest('id')
                    ->first();

                if (! $appointment) {
                    continue;
                }

                // Get FIRST paid payment record
                $payment = DirectAppointmentPayment::where('direct_appointment_id', $appointment->id)
                    ->orderBy('id', 'asc')            // first payment
                    ->first();

                if ($payment) {
                    $payment->update([
                        'status' => $newStatus,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Appointments handled successfully.',
            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // refactored single appointment
    public function refSingleAppointment($sales_order_id)
    {

        // 🟩 Call the service
        $response = $this->singleAppointment($sales_order_id);
        $response = json_decode($response->getContent())->data;

        // 🟨 Basic validation of the root object
        if (empty($response) || ! isset($response->Status) || $response->Status !== true) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or empty response from service',
            ]);
        }

        $appointment = null;

        if (isset($response->Data->Appointments) && is_array($response->Data->Appointments) && count($response->Data->Appointments) > 0) {
            // Old format (Appointments inside Data)
            $appointment = $response->Data->Appointments[0];
        } elseif (isset($response->Data->SalesOrderId)) {
            // New format (Data directly has fields)
            $appointment = $response->Data;
        }

        if (! $appointment) {
            return response()->json([
                'status' => false,
                'message' => 'No appointment found for this Sales Order ID',
            ]);
        }

        return $data = [
            'sales_order_id' => $appointment->SalesOrderId ?? null,
            'required_amount' => $appointment->RequiredAmount ?? 0,
            'book_id' => $appointment->BookId ?? null,
            'Worker' => $appointment->Worker ?? null,
            'sales_lines' => $appointment->SalesLines ?? [],
        ];
    }

    // edited single appointment
    public function refSingleAppointment2($sales_order_id)
    {
        $response = $this->dyService->getAppointmentBySalesOrder($sales_order_id);
        if (
            isset($response['Status']) && $response['Status'] === true &&
            isset($response['Data']) &&
            is_array($response['Data'])
        ) {
            // return $response['Data']['Products'];

            return $data = [
                'sales_order_id' => $response['Data']['SalesOrderId'] ?? null,
                'required_amount' => $response['Data']['RequiredAmount'] ?? 0,
                'book_id' => $response['Data']['BookId'] ?? null,
                'Worker' => $response['Data']['Worker'] ?? null,
                'sales_lines' => $response['Data']['SalesLines'] ?? [],
            ];
        }

        // 🟨 Basic validation of the root object
        return $data = [
            'sales_order_id' => 'not found' ?? null,
            'required_amount' => 'not found' ?? 0,
            'book_id' => 'not found' ?? null,
            'Worker' => 'not found' ?? null,
            'sales_lines' => 'not found' ?? [],
        ];
    }

    // edited single appointment
    public function refSingleAppointmentByBookId(string $book_id): ?array
    {
        $response = $this->dyService->getAppointmentByBookId($book_id);

        if (
            ! isset($response['Status']) ||
            $response['Status'] !== true ||
            ! isset($response['Data']) ||
            ! is_array($response['Data'])
        ) {
            return [
                'sales_order_id' => 'not found',
                'required_amount' => 'not found',
                'book_id' => 'not found',
                'Worker' => 'not found',
                'Status' => $response['Status'] ?? null,
                'used_balance' => 'not found',
                'sales_lines' => [],
            ];
        }

        $data = $response['Data'];
        $salesLines = $data['SalesLines'] ?? [];

        // $rec = User::where('tech_id', $data['Worker'])->orderByDesc('id')->first();
        $rec = $this->getTechnicianUser($data['Worker']);
        $authenticatedUser = $rec ?? Auth::user();
        // ✅ If Status is empty string, treat as non-actionable — return null
        if (($data['Status'] ?? '') === '') {
            return null;
        }

        // ✅ Build stock map only for serial items
        $serialLines = array_filter($salesLines, fn($l) => (bool) ($l['IsSerial'] ?? false));

        $stockMap = ! empty($serialLines)
            ? $this->buildStockMapForSalesLines($serialLines, $authenticatedUser->warehouse_id)
            : collect();

        // ✅ Attach available_serials and selected_serials to each line
        $salesLines = array_map(function ($line) use ($stockMap) {
            $line = $this->resolveSerialData($line, $stockMap);

            // ✅ Get selected serials from DB
            $salesLineRecId = $line['SaleslineId'] ?? null;
            $line['selected_serials'] = $salesLineRecId
                ? AppointmentTransactionSerial::where('sales_line_rec_id', $salesLineRecId)
                ->pluck('serial')
                ->values()
                ->toArray()
                : [];

            return $line;
        }, $salesLines);

        return [
            'sales_order_id' => $data['SalesOrderId'] ?? null,
            'required_amount' => $data['RequiredAmount'] ?? 0,
            // ⚠ FIXED: this key was missing entirely — every read of
            // $appointmentData['PaidAmount'] across Steps 1, 2, 3, 5, 6,
            // and 7 of the required_amount rework has been silently
            // falling back to 0 via `?? 0`, regardless of what DY365
            // actually reported, because this array never included it.
            'PaidAmount' => $data['PaidAmount'] ?? 0,
            'TotalAmountSum' => $data['TotalAmountSum'] ?? 0,
            'OrderTypeId' => $data['OrderTypeId'] ?? null,
            'book_id' => $data['BookId'] ?? null,
            'Worker' => $data['Worker'] ?? null,
            'Status' => $data['Status'] ?? null,
            'used_balance' => $data['UsedBalance'] ?? 0,
            'sales_lines' => $salesLines,
        ];
    }

    public function storeAttachments(StoreAppointmentAttachmentsRequest $request)
    {
        $data = $request->validated();

        $attachmentUrls = [];
        $completeFormUrls = [];

        // 0) check stock for all items before proceeding
        $stockCheck = $this->salesLinesSummaryByBookId($data['book_id']);
        if ($stockCheck !== null) {
            return $stockCheck; // returns the 400 error response
        }

        try {
            // ── Get appointment ───────────────────────────────────────────────────
            $appointment = DirectAppointment::where('sales_order_id', $data['sales_order_id'])
                ->where('book_id', $data['book_id'])
                ->orderByDesc('id')
                ->first();

            if (! $appointment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Appointment not found.',
                ], 404);
            }

            if ($appointment->status !== 'paid') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Appointment is not paid.',
                ], 422);
            }

            /**
             * ──────────────────────────────────────────────────────────────────────
             * 0️⃣  Pre-check: verify appointment is eligible before doing anything
             * ──────────────────────────────────────────────────────────────────────
             */
            $dispatcher = app(PaymentCompletionDispatcher::class);
            $check = $dispatcher->preCheck($appointment);

            if (! ($check['ok'] ?? false)) {
                Log::warning('storeAttachments blocked by pre-check', [
                    'appointment_id' => $appointment->id,
                    'sales_order_id' => $data['sales_order_id'],
                    'book_id' => $data['book_id'],
                    'reason' => $check['reason'] ?? 'unknown',
                    'check' => $check,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Appointment is not eligible for submission.',
                    'reason' => $check['reason'] ?? 'unknown',
                ], 422);
            }

            // ── Update appointment notes only ─────────────────────────────────────
            if (array_key_exists('notes', $data)) {
                $appointment->update(['notes' => $data['notes']]);
            }

            /**
             * ──────────────────────────────────────────────────────────────────────
             * 1️⃣  Store OLD attachments (direct_appointment_attachments)
             * ──────────────────────────────────────────────────────────────────────
             */
            if ($request->hasFile('images')) {
                foreach ((array) $request->file('images') as $index => $image) {
                    if (! $image || ! $image->isValid()) {
                        continue;
                    }

                    $path = $this->storeAttachment($image, $data['sales_order_id']);

                    DirectAppointmentAttachment::create([
                        'direct_appointment_id' => $appointment->id,
                        'image' => $path,
                    ]);

                    $url = Storage::disk('s3')->url($path);

                    if (! empty($url)) {
                        $attachmentUrls[] = [
                            'URL' => $url,
                            'Description' => "images[{$index}]",
                        ];
                    }
                }
            }

            /**
             * ──────────────────────────────────────────────────────────────────────
             * 2️⃣  Store Complete Form + its images
             * ──────────────────────────────────────────────────────────────────────
             */
            $completeFormData = Arr::only($data, [
                'sales_order_id',
                'book_id',
                'service_name',
                'home_salt',
                'device_salt',
                'carbon_depletion',
                'sink_cleaning',
                'drain_connection',
                'problem',
                'solution',
            ]);

            $completeFormData['appointment_id'] = $appointment->id;

            $imageFields = [
                'home_salt_image',
                'device_salt_image',
                'carbon_depletion_image',
                'sink_cleaning_image',
                'drain_connection_image',
                'additional_image',
            ];

            foreach ($imageFields as $field) {
                // ── additional_image: normalize single file → array ───────────────
                if ($field === 'additional_image') {
                    if ($request->hasFile('additional_image')) {

                        $paths = [];
                        foreach ((array) $request->file('additional_image') as $index => $file) {
                            if (! $file || ! $file->isValid()) {
                                continue;
                            }

                            $path = $this->storeAttachment($file, $data['sales_order_id']);
                            $paths[] = $path;

                            $url = Storage::disk('s3')->url($path);
                            if (! empty($url)) {
                                $completeFormUrls[] = [
                                    'URL' => $url,
                                    'Description' => "additional_image[{$index}]",
                                ];
                            }
                        }

                        $completeFormData['additional_image'] = $paths ?: null;
                    }

                    continue;
                }

                // ── single file fields ────────────────────────────────────────────
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $path = $this->storeAttachment($request->file($field), $data['sales_order_id']);
                    $completeFormData[$field] = $path;

                    $url = Storage::disk('s3')->url($path);
                    if (! empty($url)) {
                        $completeFormUrls[] = [
                            'URL' => $url,
                            'Description' => $field,
                        ];
                    }
                }
            }

            $completeForm = CompleteForm::create($completeFormData);

            /**
             * ──────────────────────────────────────────────────────────────────────
             * 3️⃣  Merge ALL image URLs
             * ──────────────────────────────────────────────────────────────────────
             */
            $allImageUrls = array_values(array_merge($attachmentUrls, $completeFormUrls));

            /**
             * ──────────────────────────────────────────────────────────────────────
             * 4️⃣  Send to Dynamics (send ALL links)
             * ──────────────────────────────────────────────────────────────────────
             */
            $note = $data['notes'] ?? $data['service_name'] ?? $data['solution'] ?? null;

            $this->dynamicsAttachmentPayloadService->queueAppointmentPayload(
                appointment: $appointment,
                note: $note,
                attachments: $allImageUrls,
            );

            /**
             * ──────────────────────────────────────────────────────────────────────
             * 5️⃣  Dispatch payment completion
             * ──────────────────────────────────────────────────────────────────────
             */
            $result = $dispatcher->dispatch($appointment);

            if (! ($result['ok'] ?? false)) {
                Log::warning('Payment completion not dispatched (storeAttachments)', [
                    'appointment_id' => $appointment->id,
                    'sales_order_id' => $data['sales_order_id'],
                    'book_id' => $data['book_id'],
                    'result' => $result,
                ]);
            } else {
                Log::info('Payment completion dispatched (storeAttachments)', [
                    'appointment_id' => $appointment->id,
                    'sales_order_id' => $data['sales_order_id'],
                    'book_id' => $data['book_id'],
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Saved successfully.',
                'attachments_urls' => $allImageUrls,
                'complete_form_id' => $completeForm->id,
            ]);
        } catch (\Throwable $e) {

            Log::error('storeAttachments failed', [
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'book_id' => $data['book_id'] ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * CompleteForm images fields saved as columns inside complete_forms
     */
    private function completeFormImageFields(): array
    {
        return [
            'home_salt_image',
            'device_salt_image',
            'carbon_depletion_image',
            'sink_cleaning_image',
            'drain_connection_image',
            'additional_image',
        ];
    }

    /**
     * Store in S3: new_attachments/{sales_order_id}
     * IMPORTANT: no ACL/visibility to avoid AccessControlListNotSupported
     */
    private function storeAttachment(UploadedFile $image, string $salesOrderId): string
    {
        $extension = $image->getClientOriginalExtension();
        $fileName = uniqid('attachment_', true) . '.' . $extension;

        $folder = "new_attachments/{$salesOrderId}";

        return $image->storeAs($folder, $fileName, 's3');
    }

    public function newTechStockWarehouse($warehouse_id): array
    {
        $pageSize = 100;
        $currentPage = 1;
        $allProducts = [];

        do {
            $payload = [
                'warehouseId' => $warehouse_id,
                'itemNumber' => '',
                'searchTerm' => '',
                'productName' => '',
                'currentPage' => $currentPage,
                'pageSize' => $pageSize,
            ];

            $response = $this->dyService->getWarehouseStockNew($payload, 3);

            if (
                $response === null ||
                ($response['Status'] ?? false) !== true ||
                ! isset($response['Data']['Products']) ||
                ! is_array($response['Data']['Products'])
            ) {
                break;
            }

            $products = $response['Data']['Products'];
            $allProducts = array_merge($allProducts, $products);

            $totalPages = $response['Data']['PagesTotal'] ?? 1;

            $currentPage++;
        } while ($currentPage <= $totalPages);

        return $allProducts;
    }

    public function testCompleteV2(Request $request)
    {
        $body = $request->all();

        // Call Dynamics service
        $response = app(DyService::class)->completeSuccessPaymentsV2($body);

        // Convert response to array (in case it returns an object)
        $responseArray = is_array($response) ? $response : (array) $response;

        // Extract needed data from the body
        $sales_order_id = $body['_contract']['SalesOrderId'] ?? null;
        $book_id = $body['_contract']['BookId'] ?? null;

        // Extract amounts from body
        $salesLines = $body['_contract']['SalesLines'] ?? [];
        $totalAmount = collect($salesLines)->sum('TotalAmount');
        $discount = (float) ($body['_contract']['Discount'] ?? 0);
        $calculatedAmount = $totalAmount + $discount;

        // If Dynamics returned an error that needs saving
        if (
            isset($responseArray['Status']) &&
            $responseArray['Status'] === false &&
            isset($responseArray['Code']) &&
            (int) $responseArray['Code'] === 400
        ) {
            CompleteIssue::create([
                'sales_order_id' => $sales_order_id,
                'required_amount' => null, // no required amount available from response
                'calculated_amount' => $calculatedAmount,
                'body' => json_encode($body),
                'error_message' => $responseArray['Error'] ?? 'Unknown error',
            ]);
        }

        return response()->json($responseArray);
    }

    // change request reasons
    public function changeRequestReasons(Request $request)
    {
        $type = $request->input('type');

        $response = $this->dyService->getChangeRequestReasons();

        if (
            isset($response['Status']) && $response['Status'] === true &&
            isset($response['Data']['ReasonsList']) &&
            is_array($response['Data']['ReasonsList'])
        ) {
            // ✅ Sync new reasons from DY to database
            foreach ($response['Data']['ReasonsList'] as $reason) {
                ChangeRequestReason::firstOrCreate(
                    ['reason_rec_id' => $reason['ReasonRecId']],
                    [
                        'reason_type' => $reason['ReasonType'],
                        'reason' => $reason['Reason'],
                        'title_ar' => 'اضف ملاحظاتك هنا',
                        'title_en' => 'Add your notes here',
                    ]
                );
            }

            // ✅ Fetch from DB with titles
            $query = ChangeRequestReason::query();

            if ($type) {
                $query->where('reason_type', $type);
            }

            $reasons = $query->get();

            return response()->json([
                'status' => true,
                'reasons' => $reasons,
            ]);
        }

        return response()->json([
            'status' => false,
            'reasons' => [],
        ]);
    }

    // change customer name
    public function changeCustomerName(Request $request)
    {
        $request->validate([
            'bookId' => 'required|string',
            'name' => 'required|string|max:255',
            'tech_id' => 'nullable|integer',
        ]);

        $logService = app(TechnicianAppointmentLogService::class);

        $bookId = $request->input('bookId');
        $techId = $request->input('tech_id') ?? auth()->user()?->tech_id;
        $newName = $request->input('name');

        $payload = [
            '_appointmentId' => $bookId,
            '_name' => $newName,
        ];

        try {
            $response = $this->dyService->changeCustomerName($payload);

            $isFailed =
                (is_array($response) && (
                    (($response['Status'] ?? null) === false) ||
                    (($response['status'] ?? null) === false) ||
                    (isset($response['Code']) && (int) $response['Code'] >= 400)
                ));

            if ($isFailed) {
                $logService->failed(
                    techId: $techId,
                    action: 'change_customer_name',
                    bookId: $bookId,
                    message: 'Failed to change customer name',
                    requestPayload: $request->all(),
                    responsePayload: $response,
                    userId: auth()->id(),
                );
            } else {
                $logService->success(
                    techId: $techId,
                    action: 'change_customer_name',
                    bookId: $bookId,
                    message: 'Customer name changed successfully',
                    requestPayload: $request->all(),
                    responsePayload: $response,
                    userId: auth()->id(),
                    meta: [
                        'new_name' => $newName,
                    ],
                );
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'change_customer_name',
                bookId: $bookId,
                message: 'changeCustomerName failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // add registration number to appointment
    public function addRegistrationNumber(Request $request)
    {
        $request->validate([
            'bookId' => 'required|string',
            'registration_number' => 'required|string|max:255',
            'tech_id' => 'nullable|integer',
        ]);

        $logService = app(TechnicianAppointmentLogService::class);

        $bookId = $request->input('bookId');
        $techId = $request->input('tech_id') ?? auth()->user()?->tech_id;
        $registrationNumber = $request->input('registration_number');

        $payload = [
            '_appointmentId' => $bookId,
            '_registrationNumber' => $registrationNumber,
        ];

        try {
            $response = $this->dyService->addRegistrationNumber($payload);

            $isFailed =
                (is_array($response) && (
                    (($response['Status'] ?? null) === false) ||
                    (($response['status'] ?? null) === false) ||
                    (isset($response['Code']) && (int) $response['Code'] >= 400)
                ));

            if ($isFailed) {
                $logService->failed(
                    techId: $techId,
                    action: 'add_registration_number',
                    bookId: $bookId,
                    message: 'Failed to add registration number',
                    requestPayload: $request->all(),
                    responsePayload: $response,
                    userId: auth()->id(),
                );
            } else {
                $logService->success(
                    techId: $techId,
                    action: 'add_registration_number',
                    bookId: $bookId,
                    message: 'Registration number added successfully',
                    requestPayload: $request->all(),
                    responsePayload: $response,
                    userId: auth()->id(),
                    meta: [
                        'registration_number' => $registrationNumber,
                    ],
                );
            }

            return response()->json($response);
        } catch (\Throwable $e) {
            $logService->failed(
                techId: $techId,
                action: 'add_registration_number',
                bookId: $bookId,
                message: 'addRegistrationNumber failed',
                requestPayload: $request->all(),
                error: $e->getMessage(),
                userId: auth()->id(),
                meta: [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            );

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // helper function
    private function resolveDyStatus(mixed $response): string
    {
        if (is_array($response)) {
            if (($response['Status'] ?? null) === false || ($response['status'] ?? null) === false) {
                return 'failed';
            }

            if (isset($response['Code']) && (int) $response['Code'] >= 400) {
                return 'failed';
            }

            return 'success';
        }

        return 'success';
    }

    public function getTechnicianUser(string $workerId): ?User
    {
        return Cache::remember(
            "technician_user:{$workerId}",
            now()->addMinutes(15),
            fn() => User::where('tech_id', $workerId)->orderByDesc('id')->first()
        );
    }

    // DELETE /integration/appointment-transactions/serials
    // Body: { "book_id": "..." }
    //
    // Deletes AppointmentTransactionSerial rows related — via
    // appointment_transaction_lines → appointment_transactions — to the
    // given book_id. Nothing is deleted from appointment_transactions or
    // appointment_transaction_lines themselves, only the serials.
    public function deleteAppointmentTransactionSerials($bookId)
    {
        $transactionIds = AppointmentTransaction::where('book_id', $bookId)->pluck('id');

        if ($transactionIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No appointment transactions found for this book_id — nothing to delete.',
                'deleted' => 0,
            ]);
        }

        $lineIds = AppointmentTransactionLine::whereIn('appointment_transaction_id', $transactionIds)->pluck('id');

        if ($lineIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No appointment transaction lines found for this book_id — nothing to delete.',
                'deleted' => 0,
            ]);
        }

        $deletedCount = AppointmentTransactionSerial::whereIn('appointment_transaction_line_id', $lineIds)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Serials deleted successfully.',
            'deleted' => $deletedCount,
        ]);
    }

    public function searchAppointmentTransactionSerial(SearchAppointmentTransactionSerialRequest $request)
    {
        $serial = trim($request->validated('serial'));

        $results = AppointmentTransactionSerial::query()
            ->where('serial', $serial)
            ->with('line.transaction')
            ->get()
            ->map(function ($serialRow) {
                $line = $serialRow->line;
                $transaction = $line?->transaction;

                return [
                    'id' => $serialRow->id,
                    'serial' => $serialRow->serial,
                    'item_number' => $serialRow->item_number,
                    'sales_line_rec_id' => $serialRow->sales_line_rec_id,
                    'book_id' => $transaction?->book_id,
                    'tech_id' => $transaction?->tech_id,
                    'transaction_id' => $transaction?->id,
                    'created_at' => $serialRow->created_at,
                ];
            })
            ->values();

        if ($results->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => "No records found for serial '{$serial}'.",
                'data' => [],
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => $results,
        ]);
    }

    public function deleteAppointmentTransactionSerial(SearchAppointmentTransactionSerialRequest $request)
    {
        $serial = trim($request->validated('serial'));

        $serialRows = AppointmentTransactionSerial::query()
            ->where('serial', $serial)
            ->with('line.transaction')
            ->get();

        if ($serialRows->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => "No records found for serial '{$serial}'.",
                'deleted' => 0,
                'kept' => 0,
            ]);
        }

        $deleted = [];
        $kept = [];

        foreach ($serialRows as $serialRow) {
            $transaction = $serialRow->line?->transaction;
            $bookId = $transaction?->book_id;

            if (! $bookId) {
                // No owning transaction/book_id at all — nothing to check
                // against DY365, but also nothing tying it to a real
                // appointment either. Leave it alone rather than guessing.
                $kept[] = [
                    'id' => $serialRow->id,
                    'message' => 'No book_id found on the owning transaction — cannot verify appointment status, so it was left alone.',
                ];

                continue;
            }

            $appointmentData = $this->refSingleAppointmentByBookId($bookId);

            // refSingleAppointmentByBookId() returns null when the
            // appointment's Status came back as an empty string (treated as
            // non-actionable/not found), or an array whose own 'Status' key
            // is the real appointment status string on success.
            $status = $appointmentData['Status'] ?? null;

            // Keep serials for appointments that are Completed, or still
            // legitimately active/pending (Delayed, Scheduled) — only
            // delete for anything else (Cancelled, unrecognized statuses)
            // or genuinely not found.
            $shouldDelete = $appointmentData === null
                || $status === null
                || $status === false
                || !in_array($status, ['Completed', 'Delayed', 'Scheduled'], true);

            if ($shouldDelete) {
                Log::info('Deleting stale appointment transaction serial.', [
                    'serial' => $serial,
                    'book_id' => $bookId,
                    'status' => $status,
                ]);

                $deleted[] = [
                    'id' => $serialRow->id,
                    'book_id' => $bookId,
                    'status' => $status,
                    'message' => 'Serial deleted.',
                ];
                $serialRow->delete();
            } else {
                $kept[] = [
                    'id' => $serialRow->id,
                    'book_id' => $bookId,
                    'status' => $status,
                    'message' => "The appointment status is {$status} — cannot delete this serial.",
                ];
            }
        }

        $topLevelMessage = match (true) {
            count($deleted) > 0 && count($kept) === 0 => count($deleted) === 1 ? 'Serial deleted.' : 'Serials deleted.',
            count($deleted) === 0 && count($kept) > 0 => 'Nothing deleted — all matching serials belong to Completed/Delayed/Scheduled appointments (or have no resolvable book_id).',
            default => 'Cleanup completed — some serials deleted, some kept.',
        };

        return response()->json([
            'status' => true,
            'message' => $topLevelMessage,
            'deleted' => count($deleted),
            'kept' => count($kept),
            'details' => [
                'deleted' => $deleted,
                'kept' => $kept,
            ],
        ]);
    }

    // GET /integration/technicians/by-primary-warehouse?main_warehouse_id=XXXX

    public function techniciansByPrimaryWarehouse(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|in:dy,db',
        ]);

        $type = $request->input('type', 'dy');

        $user = $request->user();

        $primaryWarehouse = $user->warehouses()
            ->wherePivot('is_primary', true)
            ->first();

        if (! $primaryWarehouse) {
            return response()->json([
                'status' => false,
                'message' => 'No primary warehouse found for the authenticated user.',
            ], 404);
        }

        // invent_location_id is where DY365's MainWarehouseId (e.g. "M006",
        // "Central") is stored locally — matches the format
        // getTechniciansByPrimaryWarehouse() expects.
        $mainWarehouseId = $primaryWarehouse->invent_location_id;

        if ($type === 'db') {
            // Local DB: technicians whose OWN primary warehouse (is_primary
            // = true on the user_warehouses pivot) matches this same
            // warehouse — no DY365 call at all. Excludes the authenticated
            // user themselves from the results.
            $technicians = User::where('type', 'tech')
                ->where('id', '!=', $user->id)
                ->whereHas('warehouses', function ($query) use ($primaryWarehouse) {
                    $query->where('warehouses.id', $primaryWarehouse->id)
                        ->where('user_warehouses.is_primary', true);
                })
                ->get();

            return response()->json([
                'status' => true,
                'source' => 'db',
                'main_warehouse_id' => $mainWarehouseId,
                'count' => $technicians->count(),
                'data' => $technicians,
            ]);
        }

        $technicians = $this->dyService->getTechniciansByPrimaryWarehouse($mainWarehouseId);

        // Find the AUTHENTICATED user's own entry within this same raw
        // list (before exclusion) to get their TechnicianDepartment —
        // this field only exists in the live DY365 response, it isn't
        // stored on the local User model at all, so this filter can only
        // apply here, not on the 'db' branch above.
        $authTechnicianEntry = collect($technicians)->first(
            fn($technician) => ($technician['TechnicianRecId'] ?? null) == $user->tech_id
        );

        $authDepartment = $authTechnicianEntry['TechnicianDepartment'] ?? null;

        // Exclude the authenticated user from the results, and — when we
        // could resolve their own department — keep only technicians in
        // that SAME TechnicianDepartment too.
        $technicians = array_values(array_filter($technicians, function ($technician) use ($user, $authDepartment) {
            if (($technician['TechnicianRecId'] ?? null) == $user->tech_id) {
                return false; // exclude self
            }

            if ($authDepartment !== null) {
                return ($technician['TechnicianDepartment'] ?? null) === $authDepartment;
            }

            // Couldn't resolve the auth user's own department (e.g. they
            // weren't found in this warehouse's list at all) — fall back
            // to warehouse-only filtering rather than returning nothing.
            return true;
        }));

        return response()->json([
            'status' => true,
            'source' => 'dy',
            'main_warehouse_id' => $mainWarehouseId,
            'technician_department' => $authDepartment,
            'count' => count($technicians),
            'data' => $technicians,
        ]);
    }

    public function deleteDirectAppointment(DeleteDirectAppointmentRequest $request)
    {
        $bookId = $request->validated('book_id');

        $appointment = DirectAppointment::where('book_id', $bookId)->first();

        if (! $appointment) {
            return response()->json([
                'status' => false,
                'message' => "No direct appointment found for book_id '{$bookId}'.",
            ], 404);
        }

        $paymentsCount = $appointment->payments()->count();

        DB::transaction(function () use ($appointment) {
            $appointment->payments()->delete();
            $appointment->delete();
        });

        Log::info('Deleted direct appointment and its payments.', [
            'book_id' => $bookId,
            'appointment_id' => $appointment->id,
            'payments_deleted' => $paymentsCount,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Direct appointment and its payments deleted successfully.',
            'deleted' => [
                'appointment_id' => $appointment->id,
                'payments_deleted' => $paymentsCount,
            ],
        ]);
    }
}
