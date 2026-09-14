<?php

namespace App\Http\Controllers\Api\Pdf;

use App\Http\Controllers\Controller;
use App\Models\InvoiceSetting;
use App\Services\DY365\DyService;
use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Throwable;

class InvoiceController extends Controller
{
    // private Arabic $arabic;

    // public function __construct(
    //     private readonly DyService $dyService
    // ) {
    //     $this->arabic = new Arabic();
    // }

    // private function ar(string $text): string
    // {
    //     if (!preg_match('/\p{Arabic}/u', $text)) {
    //         return $text;
    //     }

    //     $p = $this->arabic->arIdentify($text);

    //     for ($i = count($p) - 1; $i >= 1; $i -= 2) {
    //         $start      = $p[$i - 1];
    //         $length     = $p[$i] - $p[$i - 1];
    //         $arabicText = mb_substr($text, $start, $length, 'UTF-8');
    //         $arabicText = $this->arabic->utf8Glyphs($arabicText);
    //         $text       = mb_substr($text, 0, $start, 'UTF-8')
    //             . $arabicText
    //             . mb_substr($text, $start + $length, null, 'UTF-8');
    //     }

    //     return $text;
    // }

    // private function reshapeArray(array $data): array
    // {
    //     foreach ($data as $key => $value) {
    //         if (is_string($value)) {
    //             $data[$key] = $this->ar($value);
    //         } elseif (is_array($value)) {
    //             $data[$key] = $this->reshapeArray($value);
    //         }
    //     }
    //     return $data;
    // }

    // private function getInvoiceLabels(): array
    // {
    //     return $this->reshapeArray(InvoiceSetting::allAsArray());
    // }

    // private function mapInvoiceResponseToPdfData(array $data): array
    // {
    //     $invoiceLines  = $data['InvoiceLines'] ?? [];
    //     $orderPayments = $data['OrderPayments'] ?? [];

    //     $items = collect($invoiceLines)->map(fn($line) => [
    //         'description' => $line['ProductName'] ?? '-',
    //         'quantity'    => (float) ($line['Quantity'] ?? 0),
    //         'price'       => (float) ($line['Price'] ?? 0),
    //         'value'       => (float) ($line['GrossAmount'] ?? 0),
    //     ])->values()->toArray();

    //     $firstPayment = $orderPayments[0] ?? [];

    //     $invoiceDate = !empty($data['InvoiceDate'])
    //         ? Carbon::parse($data['InvoiceDate'])->format('Y-m-d')
    //         : now()->format('Y-m-d');

    //     $customerAddress = isset($data['CustomerAddress'])
    //         ? str_replace("\n", ' - ', $data['CustomerAddress'])
    //         : '-';

    //     return [
    //         'number'              => $data['InvoiceId'] ?? '-',
    //         'date'                => $invoiceDate,
    //         'printed_at'          => now()->format('Y-m-d H:i:s'),
    //         'registration_number' => $data['RegistrationNumber'] ?? '-',
    //         'sales_person'        => $data['SalesmanName'] ?? '-',
    //         'customer' => [
    //             'name'    => $data['CustomerName'] ?? '-',
    //             'number'  => $data['CustomerAccount'] ?? '-',
    //             'address' => $customerAddress,
    //         ],
    //         'items'  => $items,
    //         'totals' => [
    //             'subtotal'       => (float) ($data['GrossTotal'] ?? 0),
    //             'discount'       => abs((float) ($data['DiscountTotal'] ?? 0)),
    //             'after_discount' => (float) ($data['TotalAfterDiscount'] ?? 0),
    //             'vat'            => (float) ($data['VAT'] ?? 0),
    //             'amount_paid'    => (float) ($data['Amount'] ?? 0),
    //             'amount_due'     => 0.00,
    //         ],
    //         'payment' => [
    //             'mode'  => $firstPayment['PaymentMethod'] ?? '-',
    //             'ref'   => $firstPayment['PaymentReference'] ?? '-',
    //             'value' => (float) ($firstPayment['Amount'] ?? ($data['Amount'] ?? 0)),
    //         ],
    //         'company' => array_merge([
    //             'address'  => '-',
    //             'city'     => '-',
    //             'country'  => '-',
    //             'phone'    => '-',
    //             'email'    => '-',
    //             'vat_no'   => '-',
    //             'name_ar'  => '-',
    //             'name_en'  => '-',
    //             'tagline'  => '-',
    //         ], InvoiceSetting::getValue('company', [])),
    //     ];
    // }


    // public function download(string $id)
    // {
    //     return $this->generatePdf($id, 'download');
    // }

    // public function stream(string $id)
    // {
    //     return $this->generatePdf($id, 'stream');
    // }

    // private function generatePdf(string $id, string $mode = 'stream')
    // {
    //     try {
    //         $response = $this->dyService->getOrCreateInvoiceByBookId(['bookId' => $id]);

    //         if (
    //             !is_array($response) ||
    //             empty($response['Status']) ||
    //             empty($response['Data']) ||
    //             !is_array($response['Data'])
    //         ) {
    //             return response()->json([
    //                 'status'   => false,
    //                 'message'  => 'Invoice not found or invalid response from service.',
    //                 'book_id'  => $id,
    //                 'response' => $response,
    //             ], 404);
    //         }

    //         $invoice    = $this->reshapeArray($this->mapInvoiceResponseToPdfData($response['Data']));
    //         $labels     = $this->getInvoiceLabels();
    //         $labels['terms'] = $this->reshapeArray([
    //             'يحق للعميل إلغاء الطلب أي وقت قبل إتمام عملية التوصيل.',
    //             'يحق للعميل صيانة المنتج من قبل فريق نقي خلال فترة الضمان.',
    //             'يحق للعميل الاسترجاع أو الاستبدال خلال سبعة أيام من تاريخ الشراء على ان تكون حالة المنتج بنفس حالته الأصلية.',
    //         ]);

    //         $logoBase64 = base64_encode(file_get_contents(public_path('images/naqi-logo.png')));
    //         $logoSrc    = 'data:image/png;base64,' . $logoBase64;
    //         $fontBase64 = base64_encode(file_get_contents(storage_path('fonts/amiri_normal_111c26a97cc8c4cf7a557808557a1147.ttf')));

    //         $qrRaw  = $response['Data']['QRCode'] ?? null;
    //         $qrData = !empty($qrRaw) ? $qrRaw : $invoice['number'];
    //         $qrCode = 'data:image/svg+xml;base64,' . base64_encode(
    //             QrCode::format('svg')->size(100)->generate($qrData)
    //         );

    //         $pdf = Pdf::loadView('pdf.invoice2', compact(
    //             'invoice',
    //             'qrCode',
    //             'fontBase64',
    //             'labels',
    //             'logoSrc'
    //         ))->setPaper('a4', 'portrait')
    //             ->setOptions([
    //                 'isRemoteEnabled'      => true,
    //                 'isHtml5ParserEnabled' => true,
    //                 'defaultFont'          => 'serif',
    //                 'fontDir'              => storage_path('fonts/'),
    //                 'fontCache'            => storage_path('fonts/'),
    //             ]);

    //         $filename = 'invoice-' . $invoice['number'] . '.pdf';

    //         return $mode === 'download'
    //             ? $pdf->download($filename)
    //             : $pdf->stream($filename);
    //     } catch (Throwable $e) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => $e->getMessage(),
    //             'book_id' => $id,
    //         ], 500);
    //     }
    // }

    // public function debug(string $id)
    // {
    //     $response = $this->dyService->getOrCreateInvoiceByBookId(['bookId' => $id]);
    //     $invoice  = $this->reshapeArray($this->mapInvoiceResponseToPdfData($response['Data']));
    //     $labels   = $this->getInvoiceLabels();

    //     return response()->json([
    //         'invoice' => $invoice,
    //         'labels'  => $labels,
    //     ]);
    // }
}
