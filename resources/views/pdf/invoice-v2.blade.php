<!DOCTYPE html>
<html lang="ar" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Document root is LTR deliberately — every element here (barcode, title, logo in the header;
           every table column; the terms' two columns) was written in the same left-to-right source order
           the approved sample shows visually. An RTL root mirrors that whole order (tables render their
           first column on the right, not the left), which is what caused the fully reversed layout this
           fixes. Arabic text itself still displays correctly: individual Arabic runs use .bdi
           (unicode-bidi: isolate) or an explicit direction: rtl (see .ar-col below) so they shape and flow
           correctly without affecting the structural layout around them. */
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            direction: ltr;
            line-height: 1.4;
        }

        .bdi {
            unicode-bidi: isolate;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: isolate;
        }

        /* ===== HEADER (page 1) ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header-table td {
            border: none;
            vertical-align: top;
            padding: 0;
        }

        .barcode-cell {
            width: 30%;
            text-align: center;
        }

        .barcode-cell img {
            max-width: 180px;
            max-height: 40px;
        }

        .barcode-number {
            font-size: 9px;
            letter-spacing: 1px;
            margin-top: 2px;
            direction: ltr;
        }

        .title-cell {
            width: 40%;
            text-align: center;
        }

        .invoice-title-ar {
            font-size: 16px;
            font-weight: bold;
            color: #0a0a0a;
        }

        .invoice-title-en {
            font-size: 10px;
            color: #444;
            direction: ltr;
        }

        .invoice-subtitle {
            font-size: 9px;
            color: #888;
            margin-top: 2px;
        }

        .logo-cell {
            width: 30%;
            text-align: right;
        }

        .logo-cell img {
            max-width: 120px;
            max-height: 90px;
        }

        .logo-company-name {
            font-size: 10px;
            font-weight: bold;
            color: #0a0a0a;
            margin-top: 2px;
        }

        .logo-company-name-en {
            font-size: 8px;
            color: #666;
            direction: ltr;
        }

        .logo-tagline {
            font-size: 7px;
            color: #999;
        }

        /* ===== PAGE 2 HEADER ===== */
        /* Same logo + company name/tagline block as the page-1 header's .logo-cell, just positioned on the
           left here instead of the right — matches the approved sample's page-2 header exactly. */
        .header2-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 1.5px solid #222;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .header2-table td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .header2-logo-cell {
            width: 50%;
            text-align: left;
        }

        .header2-logo-cell img {
            max-width: 60px;
            max-height: 45px;
        }

        .header2-title-cell {
            width: 50%;
            text-align: right;
        }

        .header2-title {
            font-size: 12px;
            font-weight: bold;
            color: #0a0a0a;
        }

        .header2-sub {
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }

        /* ===== INFO BAR ===== */
        table.info-bar {
            width: 100%;
            border: 1px solid #333;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.info-bar td {
            border: 1px solid #333;
            padding: 4px 6px;
            vertical-align: top;
            font-weight: bold;
        }

        .info-label {
            display: block;
            font-size: 7.5px;
            color: #666;
        }

        .info-value {
            font-weight: bold;
            color: #111;
            font-size: 9px;
        }

        /* ===== ITEMS TABLE ===== */
        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table.items-table th,
        table.items-table td {
            border: 1px solid #333;
            padding: 4px 5px;
            text-align: center;
            font-weight: bold;
        }

        table.items-table th {
            background: #d9d9d9;
            font-weight: bold;
            font-size: 7.5px;
            line-height: 1.5;
        }

        table.items-table th .en {
            display: block;
            direction: ltr;
            font-weight: normal;
            color: #555;
        }

        table.items-table td.desc {
            text-align: right;
        }

        table.items-table td.desc .en {
            direction: ltr;
            display: block;
        }

        table.items-table td.desc .ar {
            display: block;
            color: #444;
        }

        .items-total-row td {
            background: #d9d9d9;
            font-weight: bold;
            border: 1px solid #333;
            padding: 4px 5px;
            text-align: center;
        }

        /* ===== VAT SUMMARY + PAYMENT DETAILS ===== */
        /* border-collapse: separate + border-spacing is what actually creates a gap BETWEEN the two cells
           here — padding on a cell only adds space on that cell's own inside edge, never between sibling
           cells in a collapsed-border table, which is why the two tables below were touching directly with
           no visible gap at all. */
        .summary-wrap {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin: 0 -10px 8px -10px;
        }

        .summary-wrap>tr>td {
            border: none;
            vertical-align: top;
            padding: 0;
            width: 50%;
        }

        table.vat-summary,
        table.payment-details {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
        }

        table.vat-summary caption,
        table.payment-details caption {
            background: #d9d9d9;
            font-weight: bold;
            font-size: 8.5px;
            padding: 4px 6px;
            text-align: center;
            caption-side: top;
            border: 1px solid #333;
            border-bottom: none;
        }

        table.vat-summary th,
        table.vat-summary td,
        table.payment-details th,
        table.payment-details td {
            border: 1px solid #333;
            padding: 3px 6px;
            font-size: 8.5px;
            text-align: center;
            font-weight: bold;
        }

        table.vat-summary th {
            background: #d9d9d9;
            font-size: 7.5px;
        }

        table.vat-summary tr.total-row td {
            font-weight: bold;
            background: #d9d9d9;
        }

        table.payment-details td.pd-label {
            text-align: right;
            font-weight: bold;
            background: #d9d9d9;
        }

        table.payment-details td.pd-value {
            text-align: left;
            direction: ltr;
        }

        table.payment-details tr.pd-total td {
            font-weight: bold;
        }

        /* ===== TAX IDS + QR ===== */
        .tax-ids-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .tax-ids-wrap td {
            border: none;
            vertical-align: top;
            padding: 0;
        }

        .tax-ids-text {
            font-size: 8px;
            line-height: 1.8;
        }

        .tax-legend {
            font-size: 7.5px;
            color: #555;
            margin-top: 2px;
        }

        .qr-cell {
            width: 85px;
            text-align: left;
        }

        .qr-cell img {
            width: 75px;
            height: 75px;
        }

        /* ===== COMPANY LINE ===== */
        table.company-line {
            width: 100%;
            border-collapse: collapse;
            border-top: 1.5px solid #222;
            border-bottom: 1.5px solid #222;
            margin-bottom: 10px;
        }

        .company-line td {
            border: none;
            padding: 6px 0;
            font-size: 7.5px;
            font-weight: bold;
            color: #1a1a1a;
            vertical-align: top;
            width: 33.33%;
        }

        .company-line .cl-en {
            text-align: left;
            direction: ltr;
        }

        .company-line .cl-mid {
            text-align: center;
            direction: ltr;
        }

        .company-line .cl-ar {
            text-align: right;
        }

        /* ===== TERMS ===== */
        .terms-heading {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        table.terms-wrap {
            width: 100%;
            border-collapse: collapse;
        }

        table.terms-wrap td {
            border: none;
            vertical-align: top;
            padding: 0;
            width: 50%;
            font-size: 8px;
            line-height: 1.7;
        }

        table.terms-wrap td.en-col {
            direction: ltr;
            text-align: left;
            padding-left: 6px;
        }

        table.terms-wrap td.ar-col {
            direction: rtl;
            text-align: right;
            padding-right: 6px;
        }

        .terms-list {
            list-style: none;
        }

        .terms-list li {
            margin-bottom: 4px;
        }

        .terms-list .num {
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
        }

        /* ===== FOOTER ===== */
        .footer-note {
            text-align: center;
            font-size: 8px;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 6px;
            margin-top: 10px;
        }

        .page-num {
            text-align: left;
            font-size: 8px;
            color: #777;
            direction: ltr;
        }

        table.footer-bar {
            width: 100%;
            border-collapse: collapse;
            border-top: 1.5px solid #222;
            border-bottom: 1.5px solid #222;
            margin-top: 14px;
        }

        .footer-bar td {
            border: none;
            padding: 8px 0;
            font-size: 9px;
            color: #1a1a1a;
            width: 33.33%;
            vertical-align: middle;
        }

        .footer-ar {
            text-align: right;
            font-weight: bold;
        }

        .footer-mid {
            text-align: center;
            direction: ltr;
            font-weight: normal;
        }

        .footer-en {
            text-align: left;
            direction: ltr;
            font-weight: bold;
        }
    </style>
</head>

<body>

    {{-- ========================= PAGE 1 ========================= --}}

    <table class="header-table">
        <tr>
            <td class="barcode-cell">
                {{-- No barcode library is installed (declined earlier when asked) — this stays text-only
                unless a barcodeSrc is explicitly passed in, so nothing renders as a broken image. --}}
                @if(!empty($barcodeSrc))
                    <img src="{{ $barcodeSrc }}" alt="Barcode">
                @endif
                <div class="barcode-number">{{ $invoice['number'] ?? '-' }}</div>
            </td>
            <td class="title-cell">
                <div class="invoice-title-ar">فاتورة ضريبية مبسطة</div>
                <div class="invoice-title-en">Simplified Tax Invoice</div>
                <div class="invoice-subtitle">نسخة إلكترونية · Digital Invoice</div>
            </td>
            <td class="logo-cell">
                <img src="{{ $logoSrc }}" alt="Logo">
                <div class="logo-company-name">{{ $invoice['company']['name_ar'] ?? '-' }}</div>
                <div class="logo-company-name-en">{{ $invoice['company']['name_en'] ?? '-' }}</div>
                <div class="logo-tagline bdi">{{ $invoice['company']['tagline'] ?? '' }}</div>
            </td>
        </tr>
    </table>

    <table class="info-bar">
        <tr>
            <td style="width:25%;"><span class="info-label">Invoice No. · رقم الفاتورة</span><span
                    class="info-value ltr">{{ $invoice['number'] ?? '-' }}</span></td>
            <td style="width:25%;"><span class="info-label">Date · التاريخ</span><span
                    class="info-value ltr">{{ $invoice['date'] ?? '-' }}</span></td>
            <td style="width:25%;"><span class="info-label">Time · الوقت</span><span
                    class="info-value ltr">{{ $invoice['time'] ?? '-' }}</span></td>
            <td style="width:25%;"><span class="info-label">Invoice Type · نوع الفاتورة</span><span
                    class="info-value">SALE · بيع</span></td>
        </tr>
        <tr>
            <td><span class="info-label">Customer · العميل</span><span
                    class="info-value bdi">{{ $invoice['customer']['name'] ?? '-' }}</span></td>
            <td><span class="info-label">Customer VAT No. · الرقم الضريبي للعميل</span><span
                    class="info-value">{{ $invoice['customer']['vat_no'] ?? '—' }}</span></td>
            <td><span class="info-label">Territory · النطاق الجغرافي</span><span
                    class="info-value bdi">{{ $invoice['customer']['territory'] ?? '—' }}</span></td>
            <td><span class="info-label">Sales Person · اسم البائع</span><span
                    class="info-value bdi">{{ $invoice['sales_person'] ?: '—' }}</span></td>
        </tr>
        <tr>
            <td colspan="4"><span class="info-label">Customer Address · عنوان العميل</span><span
                    class="info-value bdi">{{ $invoice['customer']['address'] ?? '-' }}</span></td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:3%;">#</th>
                <th style="width:28%;">الوصف<span class="en">Description</span></th>
                <th style="width:7%;">الكمية<span class="en">QTY</span></th>
                <th style="width:12%;">سعر الوحدة<span class="en">Unit Price excl. VAT</span></th>
                <th style="width:10%;">القيمة<span class="en">Value</span></th>
                <th style="width:10%;">الخصم<span class="en">Discount</span></th>
                <th style="width:12%;">الصافي<span class="en">Net excl. VAT</span></th>
                <th style="width:6%;">الضريبة<span class="en">VAT</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice['items'] as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="desc">
                        <span class="en">{{ $item['description_en'] ?? $item['description'] ?? '-' }}</span>
                        @if(!empty($item['description_ar']))<span class="ar bdi">{{ $item['description_ar'] }}</span>@endif
                    </td>
                    <td>{{ rtrim(rtrim(number_format($item['quantity'] ?? 0, 2), '0'), '.') }}</td>
                    <td>{{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['value'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['discount'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['net'] ?? (($item['value'] ?? 0) - ($item['discount'] ?? 0)), 2) }}</td>
                    <td>{{ $item['vat_code'] ?? 'S' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="items-total-row">
                <td colspan="2">Total (SAR) · الإجمالي</td>
                <td>{{ count($invoice['items']) }}</td>
                <td></td>
                <td>{{ number_format($invoice['totals']['subtotal'] ?? 0, 2) }}</td>
                <td>{{ number_format($invoice['totals']['discount'] ?? 0, 2) }}</td>
                <td>{{ number_format($invoice['totals']['after_discount'] ?? 0, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <table class="summary-wrap">
        <tr>
            <td>
                <table class="vat-summary">
                    <caption>ملخص الضريبة المضافة · VAT Summary</caption>
                    <tr>
                        <th>رمز<br>Code</th>
                        <th>قبل الضريبة<br>Ex VAT</th>
                        <th>مبلغ الضريبة<br>VAT</th>
                        <th>شامل الضريبة<br>Inc VAT</th>
                    </tr>
                    <tr>
                        <td>S</td>
                        <td>{{ number_format($invoice['totals']['after_discount'] ?? 0, 2) }}</td>
                        <td>{{ number_format($invoice['totals']['vat'] ?? 0, 2) }}</td>
                        <td>{{ number_format(($invoice['totals']['after_discount'] ?? 0) + ($invoice['totals']['vat'] ?? 0), 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td>O</td>
                        <td>0.00</td>
                        <td>0.00</td>
                        <td>0.00</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total</td>
                        <td>{{ number_format($invoice['totals']['after_discount'] ?? 0, 2) }}</td>
                        <td>{{ number_format($invoice['totals']['vat'] ?? 0, 2) }}</td>
                        <td>{{ number_format(($invoice['totals']['after_discount'] ?? 0) + ($invoice['totals']['vat'] ?? 0), 2) }}
                        </td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="payment-details">
                    <caption>معلومات الدفع · Payment Details</caption>
                    <tr>
                        <td class="pd-label bdi">{{ $invoice['payment']['mode'] ?? '-' }} · Receipt No.
                            {{ $invoice['payment']['ref'] ?? '-' }}</td>
                        <td class="pd-value">{{ number_format($invoice['payment']['value'] ?? 0, 2) }}</td>
                    </tr>
                    <tr class="pd-total">
                        <td class="pd-label">المبلغ المدفوع · Total Paid</td>
                        <td class="pd-value">{{ number_format($invoice['totals']['amount_paid'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="pd-label">الباقي · Change</td>
                        <td class="pd-value">0.00</td>
                    </tr>
                    <tr>
                        <td class="pd-label">تعديل الباقي · Change Adjustment</td>
                        <td class="pd-value">0.00</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="tax-ids-wrap">
        <tr>
            <td>
                <div class="tax-ids-text">
                    <div><strong>VAT ID: {{ $invoice['company']['vat_no'] ?? '-' }}</strong> · الرقم الضريبي</div>
                    <div><strong>Unified No.: {{ $invoice['company']['unified_number'] ?? '-' }}</strong> · الرقم الموحد
                    </div>
                </div>
                <div class="tax-legend">"S" VAT inclusive @ 15% شامل ضريبة القيمة المضافة 15% &nbsp;&nbsp; <br> "O" VAT not
                    applicable غير خاضع لضريبة القيمة المضافة</div>
            </td>
            <td class="qr-cell">
                <img src="{{ $qrCode }}" alt="QR Code">
            </td>
        </tr>
    </table>

    <table class="company-line">
        <tr>
            <td class="cl-en">{{ $invoice['company']['name_en'] ?? '-' }}, {{ $invoice['company']['address'] ?? '-' }},
                {{ $invoice['company']['city'] ?? '-' }}, {{ $invoice['company']['country'] ?? '-' }}.</td>
            <td class="cl-mid bdi">Unified Number: {{ $invoice['company']['unified_number'] ?? '-' }} · الرقم الموحد
            </td>
            <td class="cl-ar bdi">{{ $invoice['company']['name_ar'] ?? '-' }}،
                {{ $invoice['company']['address'] ?? '-' }}، {{ $invoice['company']['city'] ?? '-' }}،
                {{ $invoice['company']['country'] ?? '-' }}</td>
        </tr>
    </table>

    <div class="terms-heading bdi">الشروط والأحكام · Terms and Conditions</div>
    <table class="terms-wrap">
        <tr>
            <td class="en-col">
                <ul class="terms-list en">
                    @foreach(array_slice($labels['terms_en'] ?? [], 0, 9) as $i => $term)
                        <li><span class="num">{{ $i + 1 }}.</span> {{ $term }}</li>
                    @endforeach
                </ul>
            </td>
            <td class="ar-col">
                <ul class="terms-list ar">
                    @foreach(array_slice($labels['terms'] ?? [], 0, 9) as $i => $term)
                        <li class="bdi"><span class="num">-{{ $i + 1 }}</span> {{ $term }}</li>
                    @endforeach
                </ul>
            </td>
        </tr>
    </table>

    {{-- This is the page-1 bottom note ("your invoice is your warranty..."), not the page-2 thank-you
    footer — it reuses .footer-bar's layout but keeps its own single top border only, no bottom
    border, matching the sample's page 1 (which continues with "Page 1/2" directly below, not a
    fully-enclosed box the way page 2's footer is). --}}
    <table class="footer-bar" style="margin-top:14px; border-top:1.5px solid #222; border-bottom:none;">
        <tr>
            <td style="text-align:left; font-size:8px; color:#555;">Your invoice is your warranty — please keep it.
                Terms &amp; conditions on page 2.</td>
            <td colspan="2" style="text-align:right; font-size:8px; color:#555;">فاتورتك هي ضمانك — يرجى المحافظة عليها.
                الشروط والأحكام في الصفحة 2.</td>
        </tr>
    </table>
    <div class="page-num">Page 1 / 2</div>

    {{-- ========================= PAGE 2 ========================= --}}
    <div class="page-break"></div>

    <table class="header2-table">
        <tr>
            <td class="header2-logo-cell">
                <img src="{{ $logoSrc }}" alt="Logo">
                <div class="logo-company-name">{{ $invoice['company']['name_ar'] ?? '-' }}</div>
                <div class="logo-company-name-en">{{ $invoice['company']['name_en'] ?? '-' }}</div>
                <div class="logo-tagline bdi">{{ $invoice['company']['tagline'] ?? '' }}</div>
            </td>
            <td class="header2-title-cell">
                <div class="header2-title bdi">{{ $invoice['number'] ?? '-' }} · فاتورة ضريبية مبسطة</div>
                <div class="header2-sub">Simplified Tax Invoice · {{ $invoice['date'] ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="terms-heading bdi">الشروط والأحكام (تابع) · Terms and Conditions (continued)</div>
    <table class="terms-wrap">
        <tr>
            <td class="en-col">
                <ul class="terms-list en">
                    @foreach(array_slice($labels['terms_en'] ?? [], 9) as $i => $term)
                        <li><span class="num">{{ $i + 10 }}.</span> {{ $term }}</li>
                    @endforeach
                </ul>
            </td>
            <td class="ar-col">
                <ul class="terms-list ar">
                    @foreach(array_slice($labels['terms'] ?? [], 9) as $i => $term)
                        <li class="bdi"><span class="num">-{{ $i + 10 }}</span> {{ $term }}</li>
                    @endforeach
                </ul>
            </td>
        </tr>
    </table>

    <table class="footer-bar">
        <tr>
            <td class="footer-en">Thank you for choosing NAQI</td>
            <td class="footer-mid">{{ $invoice['company']['email'] ?? 'contact@naqi.sa' }} ·
                {{ $invoice['company']['unified_number'] ?? '' }}</td>
            <td class="footer-ar">شكراً لاختياركم نقي</td>
        </tr>
    </table>
    <div class="page-num">Page 2 / 2</div>

</body>

</html>
