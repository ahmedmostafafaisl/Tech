<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة نقي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            direction: rtl;
            text-align: right;
            font-family: 'DejaVu Sans', sans-serif !important;
        }
    </style>
    <style>
        @font-face {
            font-family: 'Amiri';
            src: url('{{ public_path("fonts/Amiri-Regular.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'Amiri', 'DejaVu Sans', sans-serif;
            direction: rtl;
            text-align: right;
        }
    </style>

    <style>

        body {
            background: #fff;
            margin: 0;
            padding: 20px;
            font-family: "Cairo", sans-serif;
            color: #000;
        }

        .invoice-container {
            direction: rtl;
            text-align: right;
            margin: 0 auto;
            padding: 1.5rem;
            max-width: 900px;
            border: 1px solid #e3e3e3;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #ccc;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            flex-direction: row-reverse;
        }

        .invoice-logo {
            width: 110px;
            height: 60px;
            object-fit: contain;
        }

        .invoice-name-ar {
            font-size: 22px;
            font-weight: 700;
            color: #444;
        }

        .invoice-name-en {
            font-size: 15px;
            color: #555;
        }

        .invoice-company-info p {
            margin: 0.2rem 0;
            font-size: 14px;
        }

        .invoice-title {
            margin-top: 1rem;
        }

        .invoice-title p {
            margin: 0.25rem 0;
            font-weight: 600;
        }

        .invoice-customer {
            margin-bottom: 2rem;
        }

        .invoice-row {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dotted #ddd;
            padding: 4px 0;
        }

        .invoice-label p {
            margin: 0;
            font-weight: 600;
            font-size: 14px;
        }

        .invoice-value p {
            margin: 0;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin-bottom: 2rem;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        th {
            background: #f8f8f8;
        }

        .invoice-summary {
            display: grid;
            grid-template-columns: 120px 1fr 1fr 1fr;
            align-items: start;
            gap: 0.5rem 1rem;
        }

        .invoice-qr {
            justify-self: center;
            align-self: center;
            width: 120px;
            height: 120px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .invoice-labels-ar,
        .invoice-labels-en,
        .invoice-values {
            font-size: 13px;
        }

        .terms {
            margin-top: 30px;
            font-size: 13px;
            line-height: 1.6;
        }

        .terms .title {
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 10px;
        }

        .terms .footer {
            font-weight: 700;
            margin-top: 10px;
        }
    </style>
</head>

<body>

    <div class="invoice-container">

        {{-- HEADER --}}
        <div class="invoice-header">
            <div class="invoice-logo-section">
                <img src="{{ public_path('images/Naqi-Logo.png') }}" alt="Naqi Logo" class="invoice-logo">
                <p class="invoice-name-ar">شركة النبع النقي للتجارة</p>
                <p class="invoice-name-en">Al Nabea Al Naaqi Trading Company</p>
                <div class="invoice-services">
                    <span>فلاتر مياه</span> |
                    <span>محطات تحلية مياه</span> |
                    <span>الرذاذ والضباب</span>
                </div>
            </div>

            <div class="invoice-company-info">
                <p>6549 وادي الشعراء - حي العليا وحدة رقم 222</p>
                <p>الرياض 12211 - 3805</p>
                <p>المملكة العربية السعودية</p>
                <p>الرقم الموحد: 920021500</p>
                <p>contact@naqi.sa</p>
                <p>الرقم الضريبي: 31036205880003</p>

                <div class="invoice-title">
                    <p>فاتورة ضريبية مبسطة Simplified Tax Invoice</p>
                    <p class="invoice-number">{{ $invoice->invoice_number ?? 'NAQI-000001' }}</p>
                    <p>تاريخ الفاتورة: {{ $invoice->date ?? now()->format('Y-m-d') }}</p>
                </div>
            </div>
        </div>

        {{-- CUSTOMER INFO --}}
        <div class="invoice-customer">
            <div class="invoice-row">
                <div class="invoice-label">
                    <p>العميل / Customer</p>
                </div>
                <div class="invoice-value">
                    <p>{{ $customer->name ?? 'N/A' }}</p>
                </div>
            </div>
            <div class="invoice-row">
                <div class="invoice-label">
                    <p>الرقم الضريبي / VAT No</p>
                </div>
                <div class="invoice-value">
                    <p>{{ $customer->vat_number ?? '---' }}</p>
                </div>
            </div>
            <div class="invoice-row">
                <div class="invoice-label">
                    <p>العنوان / Address</p>
                </div>
                <div class="invoice-value">
                    <p>{{ $customer->address ?? '---' }}</p>
                </div>
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الوصف - Description</th>
                    <th>الكمية - Qty</th>
                    <th>السعر - Price</th>
                    <th>القيمة - Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->price, 2) }}</td>
                        <td>{{ number_format($item->quantity * $item->price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- SUMMARY --}}
        <div class="invoice-summary">
            <div class="invoice-qr">
                @if(isset($invoice->qr_code))
                    <img src="data:image/png;base64,{{ $invoice->qr_code }}" alt="QR Code" width="110" height="110">
                @else
                    QR CODE
                @endif
            </div>

            <div class="invoice-labels-ar">
                <p>الاجمالي</p>
                <p>الخصم</p>
                <p>الاجمالي بعد الخصم</p>
                <p>ضريبة القيمة المضافة 15%</p>
                <p>الاجمالي شامل الضريبة</p>
                <p>اجمالي المدفوع</p>
                <p>المبلغ المستحق</p>
            </div>

            <div class="invoice-values">
                <p>{{ number_format($invoice->subtotal, 2) }}</p>
                <p>{{ number_format($invoice->discount ?? 0, 2) }}</p>
                <p>{{ number_format($invoice->subtotal - ($invoice->discount ?? 0), 2) }}</p>
                <p>{{ number_format($invoice->vat_amount ?? 0, 2) }}</p>
                <p>{{ number_format($invoice->total ?? 0, 2) }}</p>
                <p>{{ number_format($invoice->paid ?? 0, 2) }}</p>
                <p>{{ number_format(($invoice->total ?? 0) - ($invoice->paid ?? 0), 2) }}</p>
            </div>

            <div class="invoice-labels-en">
                <p>Total Amount</p>
                <p>Discount</p>
                <p>Total After Discount</p>
                <p>VAT 15%</p>
                <p>Total With VAT</p>
                <p>Total Paid</p>
                <p>Amount Due</p>
            </div>
        </div>

        {{-- TERMS --}}
        <div class="terms">
            <h5 class="title">الشروط و الأحكام</h5>
            <p>جميع المنتجات الخاصة بشركة نقي التي تباع على المتجر الإلكتروني أو المنصات الأخرى مضمونة لمدة عامين ضمان
                أجهزة التحلية.</p>
            <p>يجب عدم تعرض الجهاز لأي أعمال تغيير أو صيانة أو نقل من شركة أخرى...</p>

            <h5 class="footer">جميع الأسعار تشمل الضريبة</h5>
            <h5 class="footer">فاتورتك هي ضمانك، يرجى المحافظة عليها</h5>
        </div>
    </div>
</body>

</html>
