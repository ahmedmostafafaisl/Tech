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
            font-size: 10px;
            color: #222;
            direction: rtl;
            line-height: 1.4;
        }

        .bdi {
            unicode-bidi: isolate;
        }

        /* ===== HEADER ===== */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
            padding: 0;
        }

        .logo-cell {
            width: 25%;
            text-align: center;
        }

        .logo-cell img {
            max-width: 110px;
            max-height: 65px;
        }

        .title-cell {
            width: 75%;
            text-align: center;
        }

        .invoice-title-ar {
            font-size: 15px;
            font-weight: bold;
            color: #111;
        }

        .invoice-title-en {
            font-size: 11px;
            color: #333;
            direction: ltr;
        }

        .invoice-subtitle {
            font-size: 10px;
            color: #777;
            margin-top: 2px;
        }

        /* ===== STORE INFO BAR ===== */
        .store-bar {
            width: 100%;
            border: 1px solid #ccc;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .store-bar td {
            border: 1px solid #ccc;
            padding: 5px 8px;
            font-size: 10px;
            vertical-align: top;
        }

        .store-bar .label {
            color: #555;
            display: block;
            font-size: 9px;
        }

        .store-bar .value {
            font-weight: bold;
            color: #111;
        }

        /* ===== TABLES ===== */
        table.items-table,
        table.totals-table,
        table.payments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        table.items-table th,
        table.items-table td,
        table.payments-table th,
        table.payments-table td {
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: center;
        }

        table.items-table th,
        table.payments-table th {
            background: #ededed;
            font-weight: bold;
            font-size: 9px;
            line-height: 1.6;
        }

        table.items-table th .en,
        table.payments-table th .en {
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
            color: #555;
            font-size: 9px;
        }

        .items-total-row td {
            background: #ededed;
            font-weight: bold;
            border: 1px solid #ccc;
            padding: 5px 6px;
            text-align: center;
        }

        /* ===== TOTALS + QR BLOCK ===== */
        .totals-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .totals-wrap>tr>td {
            border: none;
            vertical-align: top;
            padding: 0;
        }

        table.totals-table td {
            border: 1px solid #ccc;
            padding: 4px 8px;
            font-size: 10px;
        }

        table.totals-table td.t-label {
            text-align: right;
            background: #f5f5f5;
            font-weight: bold;
            width: 55%;
        }

        table.totals-table td.t-value {
            text-align: left;
            direction: ltr;
            width: 45%;
        }

        table.totals-table tr.grand-total td {
            background: #ededed;
            font-weight: bold;
            font-size: 11px;
        }

        .qr-cell {
            width: 110px;
            text-align: center;
            vertical-align: top;
            padding-right: 10px;
        }

        .qr-cell img {
            width: 95px;
            height: 95px;
            border: 1px solid #ccc;
            padding: 4px;
        }

        /* ===== VAT ID LINE ===== */
        .tax-ids {
            text-align: center;
            font-size: 9px;
            color: #333;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 5px 0;
            margin-bottom: 10px;
        }

        /* ===== TERMS ===== */
        .terms-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .terms-wrap td {
            border: none;
            vertical-align: top;
            padding: 0;
            width: 50%;
            font-size: 9px;
            line-height: 1.8;
        }

        .terms-wrap td.en-col {
            direction: ltr;
            text-align: left;
            padding-left: 10px;
        }

        .terms-wrap td.ar-col {
            direction: rtl;
            text-align: right;
            padding-right: 10px;
        }

        .terms-list {
            list-style: none;
        }

        /* ===== FOOTER ===== */
        .footer-bar {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #ccc;
            margin-top: 12px;
        }

        .footer-bar td {
            border: none;
            padding-top: 8px;
            font-size: 10px;
            color: #333;
            width: 33.33%;
            vertical-align: middle;
        }

        .footer-ar {
            text-align: right;
        }

        .footer-mid {
            text-align: center;
            direction: ltr;
            color: #00a651;
            font-weight: bold;
        }

        .footer-en {
            text-align: left;
            direction: ltr;
        }
    </style>
</head>

<body>

    {{-- ===== HEADER ===== --}}
    <table class="header-table">
        <tr>
            <td class="title-cell">
                <div class="invoice-title-ar">{{ $labels['invoice_title'] ?? 'فاتورة ضريبية مبسطة' }}</div>
                <div class="invoice-title-en">{{ $labels['invoice_title_en'] ?? 'Digital Invoice' }}</div>
                <div class="invoice-subtitle">{{ $labels['invoice_subtitle'] ?? 'نسخة إلكترونية' }}</div>
            </td>
            <td class="logo-cell">
                <img src="{{ $logoSrc }}" alt="Logo">
            </td>
        </tr>
    </table>

    {{-- ===== STORE / INVOICE INFO BAR ===== --}}
    <table class="store-bar">
        <tr>
            <td style="width:34%;">
                <span class="label">{{ $invoice['company']['name_ar'] ?? '-' }}</span>
                <span class="value bdi">{{ $invoice['company']['name_en'] ?? '-' }}</span>
            </td>
            <td style="width:33%;">
                <span class="label">رقم الفاتورة - Invoice No.</span>
                <span class="value bdi">{{ $invoice['number'] ?? '-' }}</span>
            </td>
            <td style="width:33%;">
                <span class="label">التاريخ والوقت - Date &amp; Time</span>
                <span class="value bdi">{{ $invoice['date'] ?? '-' }}</span>
            </td>
        </tr>
    </table>

    {{-- ===== ITEMS TABLE ===== --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:40%;">الصنف<span class="en">Item</span></th>
                <th style="width:12%;">الكمية<span class="en">QTY</span></th>
                <th style="width:16%;">سعر الوحدة<span class="en">Unit Price</span></th>
                <th style="width:16%;">القيمة<span class="en">Value</span></th>
                <th style="width:16%;">الاجمالي<span class="en">Total</span></th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice['items'] as $item)
                <tr>
                    <td class="desc">
                        <bdi>{{ $item['description'] ?? '-' }}</bdi>
                    </td>
                    <td>{{ $item['quantity'] ?? '-' }}</td>
                    <td>{{ number_format($item['price'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['value'] ?? 0, 2) }}</td>
                    <td>{{ number_format($item['value'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="items-total-row">
                <td colspan="4" style="text-align:right;">
                    الاجمالي الكلي - Total (SAR)
                </td>
                <td>{{ number_format($invoice['totals']['after_discount'] ?? 0, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ===== TOTALS + QR ===== --}}
    <table class="totals-wrap">
        <tr>
            <td class="qr-cell">
                <img src="{{ $qrCode }}" alt="QR Code">
            </td>
            <td>
                <table class="totals-table">
                    <tr>
                        <td class="t-label">{{ $labels['total'] ?? 'الاجمالي' }} - Subtotal</td>
                        <td class="t-value">{{ number_format($invoice['totals']['subtotal'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="t-label">{{ $labels['discount'] ?? 'الخصم' }} - Discount</td>
                        <td class="t-value">{{ number_format($invoice['totals']['discount'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="t-label">{{ $labels['vat'] ?? 'ضريبة القيمة المضافة' }} - VAT</td>
                        <td class="t-value">{{ number_format($invoice['totals']['vat'] ?? 0, 2) }}</td>
                    </tr>
                    <tr class="grand-total">
                        <td class="t-label">{{ $labels['amount_paid'] ?? 'المبلغ المدفوع' }} - Total Paid</td>
                        <td class="t-value">{{ number_format($invoice['totals']['amount_paid'] ?? 0, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== PAYMENT DETAILS ===== --}}
    <table class="payments-table">
        <thead>
            <tr>
                <th style="width:40%;">طريقة الدفع<span class="en">Payment Method</span></th>
                <th style="width:35%;">المرجع<span class="en">Reference</span></th>
                <th style="width:25%;">المبلغ<span class="en">Amount</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse(($invoice['payments'] ?? []) as $p)
                <tr>
                    <td><bdi>{{ $p['mode'] ?? '-' }}</bdi></td>
                    <td><bdi>{{ $p['ref'] ?? '-' }}</bdi></td>
                    <td>{{ number_format($p['value'] ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td><bdi>{{ $invoice['payment']['mode'] ?? '-' }}</bdi></td>
                    <td><bdi>{{ $invoice['payment']['ref'] ?? '-' }}</bdi></td>
                    <td>{{ number_format($invoice['payment']['value'] ?? 0, 2) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- ===== VAT / CR / UNIFIED NUMBER LINE ===== --}}
    <div class="tax-ids">
        {{ $labels['tax_no'] ?? 'الرقم الضريبي' }} - VAT ID: <bdi>{{ $invoice['company']['vat_no'] ?? '-' }}</bdi>
        @if(!empty($invoice['company']['cr_number']))
            &nbsp;&nbsp;|&nbsp;&nbsp; CR#: <bdi>{{ $invoice['company']['cr_number'] }}</bdi>
        @endif
        @if(!empty($invoice['company']['unified_number']))
            &nbsp;&nbsp;|&nbsp;&nbsp; {{ $labels['unified_no'] ?? 'الرقم الموحد' }}:
            <bdi>{{ $invoice['company']['unified_number'] }}</bdi>
        @endif
    </div>

    {{-- ===== COMPANY LINE ===== --}}
    <div style="text-align:center; font-size:9px; color:#555; margin-bottom:6px;">
        <bdi>{{ $invoice['company']['name_ar'] ?? '-' }}</bdi> — <bdi>{{ $invoice['company']['address'] ?? '-' }}</bdi>,
        <bdi>{{ $invoice['company']['city'] ?? '-' }}</bdi>, <bdi>{{ $invoice['company']['country'] ?? '-' }}</bdi>
    </div>

    {{-- ===== TERMS ===== --}}
    {{-- Arabic terms (labels['terms']) are the real, existing legal/business content — 24 actual terms
    about returns, warranty, and fees, not placeholder text. There is no English translation of them
    anywhere in the system (confirmed against InvoiceSettingSeeder), and this view does not invent one:
    the English column reads from a SEPARATE setting key, terms_en, left for an admin to fill in via
    the existing PUT /api/invoice-settings/{key} endpoint. Until that's set, the Arabic column spans
    the full width rather than leaving a confusingly empty English column beside it. --}}
    @if(!empty($labels['terms']) || !empty($labels['terms_en']))
        <table class="terms-wrap">
            <tr>
                <td class="ar-col" style="{{ empty($labels['terms_en']) ? 'width:100%;' : '' }}">
                    @if(!empty($labels['terms']))
                        <ul class="terms-list">
                            @foreach($labels['terms'] as $term)
                                <li>{{ $term }}</li>
                            @endforeach
                        </ul>
                    @endif
                </td>
                @if(!empty($labels['terms_en']))
                    <td class="en-col">
                        <ul class="terms-list">
                            @foreach($labels['terms_en'] as $term)
                                <li>{{ $term }}</li>
                            @endforeach
                        </ul>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    {{-- ===== FOOTER ===== --}}
    <table class="footer-bar">
        <tr>
            <td class="footer-ar">{{ $labels['footer_ar'] ?? 'شكراً لثقتكم بنا.' }}</td>
            <td class="footer-mid">{{ $labels['footer_site'] ?? 'naqi.sa' }}</td>
            <td class="footer-en">{{ $labels['footer_en'] ?? 'Thank you for trusting Naqi.' }}</td>
        </tr>
    </table>

</body>

</html>
