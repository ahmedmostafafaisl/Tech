<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة نقي</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: #f7f7f7;
            margin: 0;
            padding: 20px;
            font-family: "Cairo", sans-serif;
        }

        .invoice-container {
            direction: rtl;
            text-align: right;
            margin: 1rem auto;
            padding: 1.5rem;
            max-width: 900px;
            background: #fff;
            border: 1px solid #e3e3e3;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
            color: #000a19;
        }

        /* HEADER */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #ccc;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            flex-direction: row-reverse;
            gap: 1rem;
        }

        .invoice-logo-section {
            text-align: center;
            color: #868686;
        }

        .invoice-logo-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .invoice-logo {
            width: 110px;
            height: 60px;
            object-fit: contain;
        }

        .invoice-name-ar {
            font-size: 24px;
            font-weight: 600;
            color: #868686;
            margin: 0.25rem 0;
        }

        .invoice-name-en {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .invoice-services {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            font-size: 14px;
            font-weight: 400;
            color: #555;
        }

        /* COMPANY INFO */
        .invoice-company-info p {
            margin: 0.2rem 0;
            font-weight: 500;
            font-size: 18px;
        }

        .invoice-title p {
            margin-top: 1rem;
            font-size: 20px;
            font-weight: 700;
        }

        .invoice-title .invoice-number {
            margin-top: 0.5rem;
        }

        /* CUSTOMER INFO */
        .invoice-customer {
            display: flex;
            flex-direction: column;
            gap: 0.6rem;
            margin-bottom: 2rem;
        }

        .invoice-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px dotted #ddd;
            padding-bottom: 0.3rem;
        }

        .invoice-label {
            flex: 0 0 35%;
        }

        .invoice-label p {
            margin: 0;
            font-weight: 600;
            font-size: 15px;
            color: #000;
            line-height: 1.2;
        }

        .invoice-value {
            flex: 1;
            text-align: left;
        }

        .invoice-value p {
            margin: 0;
            font-weight: 400;
            color: #555;
            font-size: 15px;
        }

        /* TABLE */
        .invoice-table-container {
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
        }

        .invoice-table th,
        .invoice-table td {
            border: 1px solid #ccc;
            padding: 0.75rem;
        }

        .invoice-table thead {
            background: #f8f8f8;
            font-weight: 600;
        }

        /* SUMMARY */
        .invoice-summary {
            display: grid;
            grid-template-columns: 120px 1fr 1fr 1fr;
            align-items: start;
            gap: 0.5rem 1rem;
            margin-bottom: 2rem;
            width: 100%;
        }

        .invoice-qr {
            justify-self: center;
            align-self: center;
            font-weight: 500;
            width: 120px;
            height: 120px;
            border: 1px solid #ddd;
            background: #fff;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .invoice-labels-ar p,
        .invoice-labels-en p,
        .invoice-values p {
            margin: 0.2rem 0;
            font-size: 14px;
            line-height: 1.4;
        }

        .invoice-labels-ar {
            text-align: right;
        }

        .invoice-values {
            text-align: center;
            font-weight: 600;
        }

        .invoice-labels-en {
            text-align: left;
        }

        .terms .title {
            font-weight: 600;
            text-decoration: underline;
            margin-bottom: 20px;
        }

        .terms .footer {
            font-weight: 600;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .invoice-header {
                flex-direction: column;
                align-items: center;
            }

            .invoice-summary {
                grid-template-columns: 1fr 1fr 1fr;
            }

            .invoice-qr {
                grid-column: 1 / -1;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
<div class="invoice-container">

    <!-- HEADER -->
    <div class="invoice-header">
        <div class="invoice-logo-section">
            <div class="invoice-logo-block">
                <img src="{{ asset('images/Naqi-Logo.png') }}" alt="Naqi Logo" class="invoice-logo" />
                <div class="invoice-company-names">
                    <p class="invoice-name-ar">شركة النبع النقي للتجارة</p>
                    <p class="invoice-name-en">Al Nabea Al Naaqi Trading Company</p>
                    <div class="invoice-services">
                        <span>فلاتر مياه</span>
                        <span>محطات تحلية مياه</span>
                        <span>الرذاذ والضباب</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="invoice-company-info">
            <p>6549 وادي الشعراء - حي العليا وحدة رقم 222</p>
            <p>الرياض 12211 - 3805</p>
            <p>المملكة العربية السعودية</p>
            <p>الرقم الموحد: 920021500</p>
            <p>contact@naqi.sa</p>
            <p id="invoice-tax-number">الرقم الضريبي: 31036205880003</p>
            <div class="invoice-title">
                <p>فاتورة ضريبية مبسطة Simplified Tax Invoice</p>
                <p class="invoice-number">NAQI-000019</p>
            </div>
        </div>
    </div>

    <!-- CUSTOMER INFO -->
    <div class="invoice-customer">
        <div class="invoice-row">
            <div class="invoice-label"><p>العميل</p><p>Customer</p></div>
            <div class="invoice-value"><p>Ahmed</p></div>
        </div>
        <div class="invoice-row">
            <div class="invoice-label"><p>الرقم الضريبي للعميل</p><p>Customer Vat No</p></div>
            <div class="invoice-value"><p>332585244558</p></div>
        </div>
        <div class="invoice-row">
            <div class="invoice-label"><p>عنوان العميل</p><p>Customer Add</p></div>
            <div class="invoice-value"><p>King Fahd 6928 Branch, Riyadh</p></div>
        </div>
    </div>

    <!-- ITEMS TABLE -->
    <div class="invoice-table-container">
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الوصف - Description</th>
                    <th>الكمية - Quantity</th>
                    <th>السعر - Price</th>
                    <th>القيمة - Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Instal-Gen</td>
                    <td>تركيب جهاز</td>
                    <td>2</td>
                    <td>$50</td>
                    <td>$100</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- SUMMARY -->
    <div class="invoice-summary">
        <div class="invoice-qr">QR CODE</div>
        <div class="invoice-labels-ar">
            <p>الاجمالي</p><p>الخصم</p><p>الاجمالي بعد الخصم</p><p>ضريبة قيمة مضافة 15%</p><p>الاجمالي شامل الضريبة</p><p>اجمالي المدفوع</p><p>المبلغ المستحق</p>
        </div>
        <div class="invoice-values">
            <p>86.96</p><p>0.00</p><p>86.96</p><p>13.04</p><p>100.00</p><p>100.00</p><p>0.00</p>
        </div>
        <div class="invoice-labels-en">
            <p>Total Amount</p><p>Discount</p><p>Total After Discount</p><p>VAT 15%</p><p>Total With VAT</p><p>Total Paid</p><p>Amount Due</p>
        </div>
    </div>

    <!-- TERMS -->
    <div class="terms">
        <h5 class="title">الشروط و الاحكام</h5>
        <p>جميع المنتجات الخاصة بشركة نقي التي تباع علي المتجر الالكتروني او المنصات الاخري مضمونة لمدة عامين ضمان اجهزة التحلية.</p>
        <p>يجب عدم تعرض الجهاز لاي اعمال تغيير أو صيانة أو نقل من شركة أخري...</p>
        <h5 class="footer">جميع الاسعار تشمل الضريبة</h5>
        <h5 class="footer">فاتورتك هي ضمانك يرجي المحافظة عليها</h5>
    </div>
</div>
</body>
</html>
