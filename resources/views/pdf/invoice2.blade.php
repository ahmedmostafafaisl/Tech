<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: A4;
            margin: 6mm;
        }

        @font-face {
            font-family: 'amiri';
            src: url("data:font/truetype;base64,{{ $fontBase64 }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'amiri';
            src: url("data:font/truetype;base64,{{ $fontBase64 }}") format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'amiri', sans-serif;
            font-size: 9px;
            color: #333;
            line-height: 1.15;
        }

        /* ===== HEADER ===== */
        .header {
            width: 100%;
            border-bottom: 1px solid #d8d8d8;
            padding: 0 14px 8px 14px;
            margin-bottom: 10px;
        }

        .header-layout {
            width: 100%;
            border-collapse: collapse;
        }

        .header-layout td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .company-info {
            width: 58%;
            font-size: 10px;
            line-height: 1.9;
            text-align: left;
            padding-left: 8px;
            direction: ltr;
        }

        .brand-block {
            width: 42%;
            text-align: center;
            direction: rtl;
            padding-right: 10px;
            padding-left: 10px;
        }

        .logo-slot {
            width: 100%;
            text-align: center;
            margin: 0 0 8px 0;
            line-height: normal;
        }

        .logo-slot img {
            width: 115px;
            max-width: 100%;
            max-height: 72px;
            display: block;
            margin: 0 auto 8px auto;
        }

        .company-name-ar {
            font-size: 16px;
            font-weight: normal;
            color: #111;
            margin-bottom: 5px;
            text-align: center;
            direction: rtl;
        }

        .company-name-en {
            font-size: 10px;
            color: #333;
            margin-bottom: 5px;
            text-align: center;
            direction: ltr;
        }

        .company-tagline {
            font-size: 9px;
            color: #777;
            margin-top: 2px;
            line-height: 1.7;
            text-align: center;
            direction: rtl;
        }

        /* ===== INVOICE TITLE ===== */
        .invoice-title {
            text-align: center;
            font-size: 12px;
            font-weight: normal;
            margin: 8px 0 2px;
            color: #222;
        }

        .invoice-number {
            text-align: center;
            font-size: 11px;
            font-weight: normal;
            margin-bottom: 8px;
            color: #111;
            direction: ltr;
        }

        /* ===== TABLES ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 3px 4px;
            text-align: center;
            line-height: 1.1;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: normal;
        }

        .compact-table th,
        .compact-table td {
            padding-top: 2px;
            padding-bottom: 2px;
        }

        /* ===== CUSTOMER INFO TABLE ===== */
        .info-table td.ar-label {
            text-align: right;
            background: #f9f9f9;
            font-weight: normal;
            width: 22%;
            direction: ltr;
        }

        .info-table td.en-label {
            text-align: left;
            background: #f9f9f9;
            font-weight: normal;
            width: 22%;
            color: #444;
        }

        .info-table td.val {
            text-align: right;
            direction: ltr;
        }

        /* ===== PAYMENT TABLE ===== */
        .payment-table {
            clear: both;
            margin-top: 4px;
        }

        .payment-table th {
            background: #e8f5e9;
        }

        /* ===== TERMS ===== */
        .terms {
            margin-top: 12px;
            margin-right: 20px;
            font-size: 10px;
            line-height: 1.9;
            direction: rtl;
            text-align: right;
        }

        .terms h3 {
            font-size: 12px;
            font-weight: normal;
            color: #00a651;
            margin-bottom: 6px;
            text-align: right;
            direction: rtl;
        }

        .terms-list {
            margin: 0;
            padding: 0;
            list-style: none;
            direction: rtl;
        }

        .terms-list li {
            margin-bottom: 4px;
            direction: rtl;
            text-align: right;
        }

        /* ===== FOOTER ===== */
        .footer-note {
            text-align: center;
            margin-top: 6px;
            font-size: 8px;
            font-weight: normal;
            color: #00a651;
        }

        .header,
        .info-table,
        .payment-table,
        .terms,
        tr,
        td,
        th {
            page-break-inside: avoid;
        }

        /* // new   */

        .terms-list {
    margin: 0;
    padding: 0;
    list-style: none;
    direction: ltr;      /* 👈 غير من rtl */
    text-align: right;   /* 👈 خلي النص على اليمين */
}

.terms-list li {
    margin-bottom: 4px;
    direction: ltr;      /* 👈 غير من rtl */
    text-align: right;   /* 👈 خلي النص على اليمين */
}

.terms {
    margin-top: 12px;
    margin-right: 20px;
    font-size: 10px;
    line-height: 1.9;
    direction: ltr;      /* 👈 غير من rtl */
    text-align: right;
}

.terms h3 {
    font-size: 12px;
    font-weight: normal;
    color: #00a651;
    margin-bottom: 6px;
    text-align: right;
    direction: ltr;      /* 👈 غير من rtl */
}
    </style>
</head>

<body>

    {{-- ===== HEADER ===== --}}
    <div class="header">
        <table class="header-layout">
            <tr>
                <td class="company-info">
                    {{ $invoice['company']['address'] ?? '-' }}<br>
                    {{ $invoice['company']['city'] ?? '-' }}<br>
                    {{ $invoice['company']['country'] ?? '-' }}<br>
                    {{ $labels['unified_no'] ?? '' }}: {{ $invoice['company']['phone'] ?? '-' }}<br>
                    {{ $invoice['company']['email'] ?? '-' }}<br>
                    {{ $labels['tax_no'] ?? '' }}: {{ $invoice['company']['vat_no'] ?? '-' }}
                </td>
            <td class="brand-block">
                <div class="logo-slot">
                    <img src="{{ $logoSrc }}" alt="Logo">
                </div>
                <div class="company-name-ar">{{ $invoice['company']['name_ar'] ?? '-' }}</div>
                <div class="company-name-en">{{ $invoice['company']['name_en'] ?? '-' }}</div>
                <div class="company-tagline">{{ $invoice['company']['tagline'] ?? '-' }}</div>
            </td>
            </tr>
        </table>
        <div class="invoice-title">{{ $labels['invoice_title'] ?? '-' }} - Simplified Tax Invoice</div>
        <div class="invoice-number">{{ $invoice['number'] ?? '-' }}</div>
    </div>

    {{-- ===== CUSTOMER INFO ===== --}}
    <table class="info-table compact-table">
        <tr>
            <td class="en-label">Customer</td>
            <td class="val">{{ $invoice['customer']['name'] ?? '-' }}</td>
            <td class="ar-label">{{ $labels['customer'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="en-label">Customer No</td>
            <td class="val">{{ $invoice['customer']['number'] ?? '-' }}</td>
            <td class="ar-label">{{ $labels['customer_no'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="en-label">Customer Add</td>
            <td class="val">{{ $invoice['customer']['address'] ?? '-' }}</td>
            <td class="ar-label">{{ $labels['customer_add'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="en-label">Registration Number</td>
            <td class="val">{{ $invoice['registration_number'] ?? '-' }}</td>
            <td class="ar-label">{{ $labels['reg_number'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="en-label">Invoice Date</td>
            <td class="val">{{ $invoice['date'] ?? '-' }}</td>
            <td class="ar-label">{{ $labels['invoice_date'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="en-label">Invoice Printed Date &amp; Time</td>
            <td class="val">{{ $invoice['printed_at'] ?? '-' }}</td>
            <td class="ar-label">{{ $labels['invoice_printed'] ?? '-' }}</td>
        </tr>
        <tr>
            <td class="en-label">Sales Person</td>
            <td class="val">{{ $invoice['sales_person'] ?? '-' }}</td>
            <td class="ar-label">{{ $labels['sales_person'] ?? '-' }}</td>
        </tr>
    </table>

    {{-- ===== ITEMS TABLE ===== --}}
    <table class="compact-table">
        <thead>
            <tr>
                <th>{{ $labels['value'] ?? '-' }} - Value</th>
                <th>{{ $labels['price'] ?? '-' }} - Price</th>
                <th>{{ $labels['quantity'] ?? '-' }} - Quantity</th>
                <th>{{ $labels['description'] ?? '-' }} - Description</th>
                <th>#</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice['items'] as $i => $item)
                <tr>
                    <td>{{ number_format($item['value'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td>{{ $item['quantity'] ?? '-' }}</td>
                    <td>{{ $item['description'] ?? '-' }}</td>
                    <td>{{ $i + 1 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== TOTALS + QR ===== --}}
    <table class="compact-table" style="width:100%; margin-top:4px; border-collapse:collapse;">
        <tr>
            <td style="width:70%; vertical-align:top; padding:0;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td style="background:#f0f0f0; border:1px solid #ddd; padding:4px 6px; text-align:right;">
                            {{ $labels['total'] ?? '-' }} - Total Amount</td>
                        <td style="border:1px solid #ddd; padding:4px 6px; text-align:center; width:100px;">
                            {{ number_format($invoice['totals']['subtotal'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="background:#f0f0f0; border:1px solid #ddd; padding:4px 6px; text-align:right;">
                            {{ $labels['discount'] ?? '-' }} - Discount</td>
                        <td style="border:1px solid #ddd; padding:4px 6px; text-align:center;">
                            {{ number_format($invoice['totals']['discount'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="background:#f0f0f0; border:1px solid #ddd; padding:4px 6px; text-align:right;">
                            {{ $labels['after_discount'] ?? '-' }} - Total After Discount</td>
                        <td style="border:1px solid #ddd; padding:4px 6px; text-align:center;">
                            {{ number_format($invoice['totals']['after_discount'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="background:#f0f0f0; border:1px solid #ddd; padding:4px 6px; text-align:right;">
                            {{ $labels['vat'] ?? '-' }} - VAT 15%</td>
                        <td style="border:1px solid #ddd; padding:4px 6px; text-align:center;">
                            {{ number_format($invoice['totals']['vat'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="background:#f0f0f0; border:1px solid #ddd; padding:4px 6px; text-align:right;">
                            {{ $labels['amount_paid'] ?? '-' }} - Amount Paid</td>
                        <td style="border:1px solid #ddd; padding:4px 6px; text-align:center;">
                            {{ number_format($invoice['totals']['amount_paid'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="background:#f0f0f0; border:1px solid #ddd; padding:4px 6px; text-align:right;">
                            {{ $labels['amount_due'] ?? '-' }} - Amount Due</td>
                        <td style="border:1px solid #ddd; padding:4px 6px; text-align:center;">
                            {{ number_format($invoice['totals']['amount_due'] ?? 0, 2) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width:30%; vertical-align:middle; text-align:center; padding:5px;">
                <img src="{{ $qrCode }}" alt="QR Code" style="width:85px;">
            </td>
        </tr>
    </table>

    {{-- ===== PAYMENT TABLE ===== --}}
    <table class="payment-table compact-table">
        <thead>
            <tr>
                <th>{{ $labels['value'] ?? '-' }} - Value</th>
                <th>{{ $labels['payment_mode'] ?? '-' }} - Mode Of Payment</th>
                <th>#</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($invoice['payment']['value'] ?? 0, 2) }}</td>
                <td>{{ $invoice['payment']['mode'] ?? '-' }}</td>
                <td>{{ $invoice['payment']['ref'] ?? '-' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ===== TERMS — صفحة جديدة ===== --}}
    <div style="page-break-before: always;" class="terms">
        <h3>{{ $labels['terms_title'] ?? '-' }}</h3>
        <ul class="terms-list">
            @foreach($labels['terms'] ?? [] as $term)
                @if(str_starts_with(trim($term), 'https://') || str_starts_with(trim($term), 'http://'))
                    <li dir="ltr" style="text-align:left;">{{ $term }}</li>
                @else
                    <li>{{ $term }}</li>
                @endif
            @endforeach
        </ul>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="footer-note">{{ $labels['footer'] ?? '-' }}</div>

</body>

</html>
