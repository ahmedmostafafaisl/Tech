<?php

namespace App\Http\Controllers\Api\Pdf;

use App\Http\Controllers\Controller;
use App\Models\InvoiceSetting;
use App\Services\DY365\DyService;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\Browsershot\Browsershot;
use Throwable;

class Invoice2Controller extends Controller
{
    public function __construct(
        private readonly DyService $dyService
    ) {}

    private function mapInvoiceResponseToPdfData(array $data): array
    {
        $invoiceLines  = $data['InvoiceLines'] ?? [];
        $orderPayments = $data['OrderPayments'] ?? [];

        $items = collect($invoiceLines)->map(fn($line) => [
            'description' => $line['ProductName'] ?? '-',
            'quantity'    => (float) ($line['Quantity'] ?? 0),
            'price'       => (float) ($line['Price'] ?? 0),
            'value'       => (float) ($line['GrossAmount'] ?? 0),
        ])->values()->toArray();

        $firstPayment = $orderPayments[0] ?? [];

        // All payments, not just the first — the payment table needs to
        // show every OrderPayments entry (e.g. split POS + CASH), not
        // only the first one.
        $allPayments = collect($orderPayments)->map(fn($payment) => [
            'mode'  => $payment['PaymentMethod'] ?? '-',
            'ref'   => $payment['PaymentReference'] ?? '-',
            'value' => (float) ($payment['Amount'] ?? 0),
        ])->values()->toArray();

        $invoiceDateTime = !empty($data['InvoiceDate']) ? Carbon::parse($data['InvoiceDate']) : now();
        // 'date' format is unchanged (pdf.invoice, the older view, shares this same mapping and already
        // expects Y-m-d) — 'time' is new, read from the same InvoiceDate value rather than a new DY365
        // field, for invoice-v2's separate Date/Time columns.
        $invoiceDate = $invoiceDateTime->format('Y-m-d');
        $invoiceTime = $invoiceDateTime->format('H:i:s');

        $customerAddress = isset($data['CustomerAddress'])
            ? str_replace("\n", ' - ', $data['CustomerAddress'])
            : '-';

        return [
            'number'              => $data['InvoiceId'] ?? '-',
            'date'                => $invoiceDate,
            'time'                => $invoiceTime,
            'printed_at'          => now()->format('Y-m-d H:i:s'),
            'registration_number' => $data['RegistrationNumber'] ?? '-',
            'sales_person'        => $data['SalesmanName'] ?? '-',
            'customer' => [
                'name'         => $data['CustomerName'] ?? '-',
                'number'       => $data['CustomerAccount'] ?? '-',
                'vat_no'       => $data['CustomerVatNumber'] ?? '—',
                'address'      => $customerAddress,
                'territory'    => $data['Territory'] ?? '—',
            ],
            'items'  => $items,
            'totals' => [
                'subtotal'       => (float) ($data['GrossTotal'] ?? 0),
                'discount'       => abs((float) ($data['DiscountTotal'] ?? 0)),
                'after_discount' => (float) ($data['TotalAfterDiscount'] ?? 0),
                'vat'            => (float) ($data['VAT'] ?? 0),
                'amount_paid'    => (float) ($data['Amount'] ?? 0),
                'amount_due'     => 0.00,
            ],
            'payment' => [
                'mode'  => $firstPayment['PaymentMethod'] ?? '-',
                'ref'   => $firstPayment['PaymentReference'] ?? '-',
                'value' => (float) ($firstPayment['Amount'] ?? ($data['Amount'] ?? 0)),
            ],
            'payments' => $allPayments,
            // Defaults below reflect Naqi's actual registered details (the sample invoice this layout was
            // built from) — still fully overridable via the invoice-settings 'company' key at any time;
            // these are just a correct baseline rather than placeholder text.
            'company' => array_merge([
                'address'        => '6549 Wadi Al Shuara, Al Olaya',
                'city'           => 'Riyadh 12211',
                'country'        => 'Saudi Arabia',
                'phone'          => '920021500',
                'email'          => 'contact@naqi.sa',
                'vat_no'         => '310362058800003',
                'unified_number' => '920021500',
                'name_ar'        => 'شركة النبع النقي للتجارة',
                'name_en'        => 'Al Nabea Al Naqi Trading Company',
                'tagline'        => 'محطات تحلية مياه - الرياض والقصيم',
            ], InvoiceSetting::getValue('company', [])),
        ];
    }

    public function download(string $id)
    {
        return $this->generatePdf($id, 'download');
    }

    public function stream(string $id)
    {
        return $this->generatePdf($id, 'stream');
    }

    private function generatePdf(string $id, string $mode = 'stream')
    {
        try {
            $response = $this->dyService->getOrCreateInvoiceByBookId(['bookId' => $id]);
            if (
                !is_array($response) ||
                empty($response['Status']) ||
                empty($response['Data']) ||
                !is_array($response['Data'])
            ) {
                return response()->view('Invoice.error', [
                    'bookId' => $id,
                ], 404);
            }

            $invoice = $this->mapInvoiceResponseToPdfData($response['Data']);
            $labels  = InvoiceSetting::allAsArray();

            $logoBase64 = base64_encode(file_get_contents(public_path('images/naqi-logo.png')));
            $logoSrc    = 'data:image/png;base64,' . $logoBase64;

            $qrRaw  = $response['Data']['QRCode'] ?? null;
            $qrData = !empty($qrRaw) ? $qrRaw : $invoice['number'];
            $qrCode = 'data:image/svg+xml;base64,' . base64_encode(
                QrCode::format('svg')->size(100)->generate($qrData)
            );

            // invoice_use_new_template (default: OFF — the original layout keeps rendering unless this is
            // explicitly turned on) switches to pdf.invoice-v2, a redesigned layout. Both views are handed
            // the exact same $invoice/$qrCode/$labels/$logoSrc data — mapInvoiceResponseToPdfData() needed
            // no change for this.
            $view = \App\Models\Setting::isActive('invoice_use_new_template', false) ? 'pdf.invoice-v2' : 'pdf.invoice';

            $html = view($view, compact(
                'invoice',
                'qrCode',
                'labels',
                'logoSrc'
            ))->render();

            $browsershot = Browsershot::html($html)
                ->setChromePath(env('CHROME_PATH', '/usr/bin/google-chrome-stable'))
                ->noSandbox()
                ->addChromiumArguments([
                    'disable-dev-shm-usage',
                    'disable-gpu',
                    'disable-setuid-sandbox',
                    'disable-extensions',
                ])
                ->setEnvironmentOptions(['HOME' => '/tmp'])
                ->setOption('waitUntil', 'load')
                ->format('A4')
                ->margins(6, 6, 6, 6)
                ->showBackground();

            $pdf = $browsershot->pdf();

            $filename = 'invoice-' . $invoice['number'] . '.pdf';

            return $mode === 'download'
                ? response($pdf, 200)->withHeaders([
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ])
                : response($pdf, 200)->withHeaders([
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'book_id' => $id,
            ], 500);
        }
    }

    public function debugChrome()
    {
        return response()->json([
            'which_google_chrome_stable' => shell_exec('which google-chrome-stable 2>&1'),
            'which_google_chrome'        => shell_exec('which google-chrome 2>&1'),
            'which_chromium'             => shell_exec('which chromium 2>&1'),
            'which_chromium_browser'     => shell_exec('which chromium-browser 2>&1'),
            'ls_usr_bin'                 => shell_exec('ls /usr/bin/google-chrome* 2>&1'),
            'ls_usr_local_bin'           => shell_exec('ls /usr/local/bin/google-chrome* 2>&1'),
            'ls_opt'                     => shell_exec('ls /opt/google/chrome* 2>&1'),
            'chrome_path_env'            => env('CHROME_PATH'),
        ]);
    }
}
