<?php

namespace App\Http\Controllers\Api\Invoice;

use App\Helper\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\InvoiceRequest;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Models\AppointmentTransaction;
use App\Models\DirectAppointment;
use App\Models\ShortLink;
use App\Repositories\Interfaces\InvoiceRepositoryInterface;
use App\Services\DY365\DyService;
use App\Services\TaqnyatSmsService;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    protected $invoiceRepository;
    protected $taqnyatSmsService;
    protected $dyService;
    use ApiResponseHelper;
    public function __construct(InvoiceRepositoryInterface $invoiceRepository,  TaqnyatSmsService $taqnyatSmsService, DyService $dyService)
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->taqnyatSmsService = $taqnyatSmsService;
        $this->dyService = $dyService;
    }

    public function index()
    {
        return InvoiceResource::collection($this->invoiceRepository->all());
    }

    public function show($id)
    {
        return new InvoiceResource($this->invoiceRepository->find($id));
    }

    public function store(InvoiceRequest $request)
    {
        return new InvoiceResource($this->invoiceRepository->create($request->validated()));
    }

    public function update(InvoiceRequest $request, $id)
    {
        return new InvoiceResource($this->invoiceRepository->update($id, $request->validated()));
    }

    public function destroy($id)
    {
        $this->invoiceRepository->delete($id);
        return response()->json(['message' => 'Deleted successfully']);
    }

    public function getByAppointment($appointmentId)
    {
        return  $invoices = $this->invoiceRepository->getByAppointment($appointmentId);
    }

    public function sendInvoiceLink(Request $request)
    {
        $request->validate([
            'phone'   => 'required|string',
            'book_id' => 'required|string',
            'sent'    => 'sometimes|boolean',
        ]);

        $bookId = $request->input('book_id');
        $pdfUrl = url('/api/invoice/' . $bookId . '/download');

        // ✅ Get latest existing record or create a new one
        $shortLink = ShortLink::where('book_id', $bookId)->latest('id')->first();

        if (!$shortLink) {
            $shortLink = ShortLink::create([
                'book_id' => $bookId,
                'code'    => $this->generateUniqueCode(),
                'url'     => $pdfUrl,
                'sent'    => false,
            ]);
        }

        $shortUrl = url('/i/' . $shortLink->code);

        // ✅ Send SMS
        if ($shortLink->sent && !$request->boolean('sent')) {
            return response()->json([
                'success'   => true,
                'message'   => 'Link already sent previously.',
                'file_url'  => $pdfUrl,
                'short_url' => $shortUrl,
            ]);
        }



        $smsResponse = $this->taqnyatSmsService->sendPdfLink(
            $request->phone,
            $shortUrl
        );

        $smsSent = is_array($smsResponse)
            && isset($smsResponse['statusCode'])
            && (int) $smsResponse['statusCode'] === 201;

        // ✅ Update sent status
        if ($smsSent) {
            $shortLink->update(['sent' => true]);
        } else {
            Log::warning('SMS إرسال فشل', [
                'book_id'  => $bookId,
                'phone'    => $request->phone,
                'response' => $smsResponse,
            ]);
        }

        return response()->json([
            'success'   => $smsSent,
            'file_url'  => $pdfUrl,
            'short_url' => $shortUrl,
            'sms'       => $smsResponse,
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = Str::random(8);
        } while (ShortLink::where('code', $code)->exists());

        return $code;
    }




    public function getInvoiceDetailsByBookId(Request $request)
    {
        $request->validate([
            'book_id' => 'required|string',
        ]);

        $bookId = $request->input('book_id');

        // ── 1. Fetch appointment from Dynamics ───────────────────────────────────
        try {
            $response = $this->dyService->getAppointmentByBookId($bookId);
        } catch (ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'This is an issue on DY365. Could not reach the service.',
            ], 503);
        } catch (InvalidFormatException $e) {
            return response()->json([
                'success' => false,
                'message' => 'This is an issue on DY365. Invalid date/time format received from service.',
                'error'   => $e->getMessage(),
            ], 422);
        }

        if (
            !is_array($response) ||
            empty($response['Status']) ||
            empty($response['Data']) ||
            !is_array($response['Data'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found.',
                'book_id' => $bookId,
            ], 404);
        }

        $data = $response['Data'];

        // ── 2. Check appointment status is Completed ─────────────────────────────
        if (($data['Status'] ?? '') !== 'Completed') {
            return response()->json([
                'success' => false,
                'message' => 'Appointment is not completed yet.',
                'book_id' => $bookId,
                'status'  => $data['Status'] ?? null,
            ], 422);
        }

        $salesOrderId = $data['SalesOrderId'] ?? null;

        // ── 3. Last or create short link ─────────────────────────────────────────
        $pdfUrl = url('/api/invoice/' . $bookId . '/download');

        $shortLink = ShortLink::where('book_id', $bookId)->latest('id')->first();

        if (!$shortLink) {
            $shortLink = ShortLink::create([
                'book_id' => $bookId,
                'code'    => $this->generateUniqueCode(),
                'url'     => $pdfUrl,
                'sent'    => false,
            ]);
        }

        $shortUrl = url('/i/' . $shortLink->code);

        // ── 3.5. Get completion date from DirectAppointment ───────────────────────
        $completionDate = null;

        $directAppointment = DirectAppointment::where('book_id', $bookId)
            ->latest('id')
            ->first();

        if ($directAppointment) {
            $callingValue = $directAppointment->complete_v2_calling;

            if (!empty($callingValue)) {
                if (str_contains($callingValue, ':')) {
                    $parts   = explode(':', $callingValue, 2);
                    $dateStr = trim($parts[1] ?? '');

                    try {
                        $completionDate = \Carbon\Carbon::parse($dateStr)->toDateTimeString();
                    } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                        // Malformed date from DY365 (e.g. "48:37") — fall back gracefully
                        $completionDate = $directAppointment->updated_at?->toDateTimeString()
                            ?? $directAppointment->created_at?->toDateTimeString();
                    }
                } else {
                    $completionDate = $directAppointment->updated_at?->toDateTimeString()
                        ?? $directAppointment->created_at?->toDateTimeString();
                }
            } else {
                $completionDate = $directAppointment->updated_at?->toDateTimeString()
                    ?? $directAppointment->created_at?->toDateTimeString();
            }
        }

        // ── 4. Get serials from DB grouped by item_number ─────────────────────────
        $transaction = AppointmentTransaction::where('book_id', $bookId)
            ->latest('id')
            ->first();

        $serialsByItemNumber = [];

        if ($transaction) {
            $lines = $transaction->lines()->with('serials')->get();

            foreach ($lines as $line) {
                $itemNumber = $line->item_number;
                $serials    = $line->serials->pluck('serial')->toArray();

                if (!isset($serialsByItemNumber[$itemNumber])) {
                    $serialsByItemNumber[$itemNumber] = [];
                }

                $serialsByItemNumber[$itemNumber] = array_merge(
                    $serialsByItemNumber[$itemNumber],
                    $serials
                );
            }
        }

        // ── 5. Build sales lines with serials ─────────────────────────────────────
        $salesLines = collect($data['SalesLines'] ?? [])
            ->map(fn($line) => [
                'item_number'   => $line['ItemNumber']  ?? null,
                'product_name'  => $line['ProductName'] ?? null,
                'sales_line_id' => $line['SaleslineId'] ?? null,
                'serials'       => $serialsByItemNumber[$line['ItemNumber'] ?? ''] ?? [],
            ])
            ->values()
            ->all();

        // ── 6. Return ─────────────────────────────────────────────────────────────
        return response()->json([
            'success'         => true,
            'book_id'         => $bookId,
            'sales_order_id'  => $salesOrderId,
            'invoice_url'     => $shortUrl,
            'customer_id'     => $data['CustomerId']          ?? null,
            'customer_name'   => $data['CustomerName']        ?? null,
            'customer_phone'  => $data['CustomerPhoneNumber'] ?? null,
            'completion_date' => $completionDate,
            'sales_lines'     => $salesLines,
        ]);
    }
}
