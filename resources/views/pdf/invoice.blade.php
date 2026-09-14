<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            direction: rtl;
            line-height: 1.4;
        }

        /* ===== HEADER ===== */
        .header {
            width: 100%;
            border-bottom: 1px solid #d8d8d8;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: top;
            padding: 0;
        }

        .company-info {
            font-size: 10px;
            line-height: 1.9;
            text-align: left;
            direction: ltr;
            width: 55%;
        }

        .brand-block {
            text-align: center;
            width: 45%;
        }

        .logo-slot img {
            width: 120px;
            max-height: 75px;
            display: block;
            margin: 0 auto 8px auto;
        }

        .company-name-ar {
            font-size: 18px;
            font-weight: bold;
            color: #111;
            margin-bottom: 4px;
        }

        .company-name-en {
            font-size: 11px;
            color: #333;
            margin-bottom: 4px;
            direction: ltr;
        }

        .company-tagline {
            font-size: 10px;
            color: #777;
            line-height: 1.6;
        }

        /* ===== INVOICE TITLE ===== */
        .invoice-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 10px 0 4px;
            color: #222;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 6px 0;
        }

        .invoice-number {
            text-align: center;
            font-size: 12px;
            margin-bottom: 10px;
            color: #111;
            direction: ltr;
        }

        /* ===== TABLES ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        table th,
        table td {
            border: 1px solid #ccc;
            padding: 4px 6px;
            text-align: center;
            line-height: 1.4;
        }

        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        /* ===== CUSTOMER INFO TABLE ===== */
        .info-table td.ar-label {
            text-align: right;
            background: #f9f9f9;
            font-weight: bold;
            width: 22%;
        }

        .info-table td.en-label {
            text-align: left;
            background: #f9f9f9;
            width: 22%;
            color: #444;
            direction: ltr;
        }

        .info-table td.val {
            text-align: center;
        }

        /* ===== PAYMENT TABLE ===== */
        .payment-table th {
            background: #e8f5e9;
        }

        /* ===== TERMS ===== */
        .terms {
            margin-top: 16px;
            font-size: 10px;
            line-height: 2;
            direction: rtl;
            text-align: right;
        }

        .terms h3 {
            font-size: 14px;
            font-weight: bold;
            color: #00a651;
            margin-bottom: 10px;
        }

        .terms-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .terms-list li {
            margin-bottom: 4px;
            text-align: right;
        }

        /* ===== FOOTER ===== */
        .footer-note {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
            font-weight: bold;
            color: #00a651;
        }

        /* ===== PAGE BREAK ===== */
        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    {{-- ===== HEADER ===== --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="brand-block">
                    <div class="logo-slot">
                        <img src="{{ $logoSrc }}" alt="Logo">
                    </div>
                    <div class="company-name-ar">{{ $invoice['company']['name_ar'] ?? '-' }}</div>
                    <div class="company-name-en">{{ $invoice['company']['name_en'] ?? '-' }}</div>
                    <div class="company-tagline">{{ $invoice['company']['tagline'] ?? '-' }}</div>
                </td>
                <td class="company-info">
                    <bdi>{{ $invoice['company']['address'] ?? '-' }}</bdi><br>
                    <bdi>{{ $invoice['company']['city'] ?? '-' }}</bdi><br>
                    <bdi>{{ $invoice['company']['country'] ?? '-' }}</bdi><br>
                    {{ $labels['unified_no'] ?? 'الرقم الموحد' }}: {{ $invoice['company']['phone'] ?? '-' }}<br>
                    {{ $invoice['company']['email'] ?? '-' }}<br>
                    {{ $labels['tax_no'] ?? 'الرقم الضريبي' }}: {{ $invoice['company']['vat_no'] ?? '-' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="invoice-title">
        {{ $labels['invoice_title'] ?? 'فاتورة ضريبية مبسطة' }} - Simplified Tax Invoice
    </div>
    <div class="invoice-number">{{ $invoice['number'] ?? '-' }}</div>

    {{-- ===== CUSTOMER INFO ===== --}}
    <table class="info-table">
        <tr>
            <td class="ar-label">{{ $labels['customer'] ?? 'العميل' }}</td>
            <td class="val">{{ $invoice['customer']['name'] ?? '-' }}</td>
            <td class="en-label">Customer</td>
        </tr>
        <tr>
            <td class="ar-label">{{ $labels['customer_no'] ?? 'رقم العميل' }}</td>
            <td class="val">{{ $invoice['customer']['number'] ?? '-' }}</td>
            <td class="en-label">Customer No</td>
        </tr>
        <tr>
            <td class="ar-label">{{ $labels['customer_add'] ?? 'عنوان العميل' }}</td>
            <td class="val"><bdi>{{ $invoice['customer']['address'] ?? '-' }}</bdi></td>
            <td class="en-label">Customer Add</td>
        </tr>
        <tr>
            <td class="ar-label">{{ $labels['reg_number'] ?? 'رقم التسجيل' }}</td>
            <td class="val">{{ $invoice['registration_number'] ?? '-' }}</td>
            <td class="en-label">Registration Number</td>
        </tr>
        <tr>
            <td class="ar-label">{{ $labels['invoice_date'] ?? 'تاريخ الفاتورة' }}</td>
            <td class="val" style="direction:ltr;">{{ $invoice['date'] ?? '-' }}</td>
            <td class="en-label">Invoice Date</td>
        </tr>
        <tr>
            <td class="ar-label">{{ $labels['invoice_printed'] ?? 'تاريخ الطباعة' }}</td>
            <td class="val" style="direction:ltr;">{{ $invoice['printed_at'] ?? '-' }}</td>
            <td class="en-label">Invoice Printed Date &amp; Time</td>
        </tr>
        <tr>
            <td class="ar-label">{{ $labels['sales_person'] ?? 'اسم البائع' }}</td>
            <td class="val">{{ $invoice['sales_person'] ?? '-' }}</td>
            <td class="en-label">Sales Person</td>
        </tr>
    </table>

    {{-- ===== ITEMS TABLE ===== --}}
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ $labels['description'] ?? 'الوصف' }} - Description</th>
                <th>{{ $labels['quantity'] ?? 'الكمية' }} - Quantity</th>
                <th>{{ $labels['price'] ?? 'السعر' }} - Price</th>
                <th>{{ $labels['value'] ?? 'القيمة' }} - Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice['items'] as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item['description'] ?? '-' }}</td>
                    <td>{{ $item['quantity'] ?? '-' }}</td>
                    <td>{{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['value'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ===== TOTALS + QR ===== --}}
    <table style="width:100%; margin-bottom:6px; border-collapse:collapse;">
        <tr>
            <td style="width:75%; vertical-align:top; padding:0; border:none;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <td
                            style="text-align:right; background:#f0f0f0; font-weight:bold; border:1px solid #ccc; padding:4px 6px;">
                            {{ $labels['total'] ?? 'الاجمالي' }}</td>
                        <td style="text-align:center; border:1px solid #ccc; padding:4px 6px; width:90px;">
                            {{ number_format($invoice['totals']['subtotal'] ?? 0, 2) }}</td>
                        <td
                            style="text-align:left; background:#f0f0f0; direction:ltr; border:1px solid #ccc; padding:4px 6px; width:140px;">
                            Total Amount</td>
                    </tr>
                    <tr>
                        <td
                            style="text-align:right; background:#f0f0f0; font-weight:bold; border:1px solid #ccc; padding:4px 6px;">
                            {{ $labels['discount'] ?? 'الخصم' }}</td>
                        <td style="text-align:center; border:1px solid #ccc; padding:4px 6px;">
                            {{ number_format($invoice['totals']['discount'] ?? 0, 2) }}</td>
                        <td
                            style="text-align:left; background:#f0f0f0; direction:ltr; border:1px solid #ccc; padding:4px 6px;">
                            Discount</td>
                    </tr>
                    <tr>
                        <td
                            style="text-align:right; background:#f0f0f0; font-weight:bold; border:1px solid #ccc; padding:4px 6px;">
                            {{ $labels['after_discount'] ?? 'الاجمالي بعد الخصم' }}</td>
                        <td style="text-align:center; border:1px solid #ccc; padding:4px 6px;">
                            {{ number_format($invoice['totals']['after_discount'] ?? 0, 2) }}</td>
                        <td
                            style="text-align:left; background:#f0f0f0; direction:ltr; border:1px solid #ccc; padding:4px 6px;">
                            Total After Discount</td>
                    </tr>
                    <tr>
                        <td
                            style="text-align:right; background:#f0f0f0; font-weight:bold; border:1px solid #ccc; padding:4px 6px;">
                            {{ $labels['vat'] ?? 'ضريبة قيمة مضافة %15' }}</td>
                        <td style="text-align:center; border:1px solid #ccc; padding:4px 6px;">
                            {{ number_format($invoice['totals']['vat'] ?? 0, 2) }}</td>
                        <td
                            style="text-align:left; background:#f0f0f0; direction:ltr; border:1px solid #ccc; padding:4px 6px;">
                            VAT 15%</td>
                    </tr>
                    <tr>
                        <td
                            style="text-align:right; background:#f0f0f0; font-weight:bold; border:1px solid #ccc; padding:4px 6px;">
                            {{ $labels['amount_paid'] ?? 'المبلغ المدفوع' }}</td>
                        <td style="text-align:center; border:1px solid #ccc; padding:4px 6px;">
                            {{ number_format($invoice['totals']['amount_paid'] ?? 0, 2) }}</td>
                        <td
                            style="text-align:left; background:#f0f0f0; direction:ltr; border:1px solid #ccc; padding:4px 6px;">
                            Amount Paid</td>
                    </tr>
                    <tr>
                        <td
                            style="text-align:right; background:#f0f0f0; font-weight:bold; border:1px solid #ccc; padding:4px 6px;">
                            {{ $labels['amount_due'] ?? 'المبلغ المستحق' }}</td>
                        <td style="text-align:center; border:1px solid #ccc; padding:4px 6px;">
                            {{ number_format($invoice['totals']['amount_due'] ?? 0, 2) }}</td>
                        <td
                            style="text-align:left; background:#f0f0f0; direction:ltr; border:1px solid #ccc; padding:4px 6px;">
                            Amount Due</td>
                    </tr>
                </table>
            </td>
            <td style="width:25%; vertical-align:middle; text-align:center; border:1px solid #ccc; padding:5px;">
                <img src="{{ $qrCode }}" alt="QR Code" style="width:90px; height:90px;">
            </td>
        </tr>
    </table>

    {{-- ===== PAYMENT TABLE ===== --}}
    <table class="payment-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ $labels['payment_mode'] ?? 'طريقة الدفع' }} - Mode Of Payment</th>
                <th>{{ $labels['value'] ?? 'القيمة' }} - Value</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice['payment']['ref'] ?? '-' }}</td>
                <td>{{ $invoice['payment']['mode'] ?? '-' }}</td>
                <td>{{ number_format($invoice['payment']['value'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ===== TERMS ===== --}}
    <div class="page-break terms">
        <h3>{{ $labels['terms_title'] ?? 'الشروط والأحكام' }}</h3>
        <ul class="terms-list">
            @foreach($labels['terms'] ?? [] as $term)
                @if(str_starts_with(trim($term), 'https://') || str_starts_with(trim($term), 'http://'))
                    <li style="direction:ltr; text-align:left;">{{ $term }}</li>
                @else
                    <li>{{ $term }}</li>
                @endif
            @endforeach
        </ul>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="footer-note">{{ $labels['footer'] ?? '' }}</div>

</body>

</html>
