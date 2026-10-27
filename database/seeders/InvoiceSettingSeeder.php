<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InvoiceSetting;

class InvoiceSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'invoice_title', 'value' => 'فاتورة ضريبية مبسطة', 'type' => 'text'],
            ['key' => 'customer', 'value' => 'العميل', 'type' => 'text'],
            ['key' => 'customer_no', 'value' => 'رقم العميل', 'type' => 'text'],
            ['key' => 'customer_add', 'value' => 'عنوان العميل', 'type' => 'text'],
            ['key' => 'reg_number', 'value' => 'رقم التسجيل', 'type' => 'text'],
            ['key' => 'invoice_date', 'value' => 'تاريخ الفاتورة', 'type' => 'text'],
            ['key' => 'invoice_printed', 'value' => 'تاريخ طباعة الفاتورة', 'type' => 'text'],
            ['key' => 'sales_person', 'value' => 'اسم البائع', 'type' => 'text'],
            ['key' => 'description', 'value' => 'الوصف', 'type' => 'text'],
            ['key' => 'quantity', 'value' => 'الكمية', 'type' => 'text'],
            ['key' => 'price', 'value' => 'السعر', 'type' => 'text'],
            ['key' => 'value', 'value' => 'القيمة', 'type' => 'text'],
            ['key' => 'total', 'value' => 'الإجمالي', 'type' => 'text'],
            ['key' => 'discount', 'value' => 'الخصم', 'type' => 'text'],
            ['key' => 'after_discount', 'value' => 'الإجمالي بعد الخصم', 'type' => 'text'],
            ['key' => 'vat', 'value' => 'ضريبة قيمة مضافة %15', 'type' => 'text'],
            ['key' => 'amount_paid', 'value' => 'المبلغ المدفوع', 'type' => 'text'],
            ['key' => 'amount_due', 'value' => 'المبلغ المستحق', 'type' => 'text'],
            ['key' => 'payment_mode', 'value' => 'طريقة الدفع', 'type' => 'text'],
            ['key' => 'unified_no', 'value' => 'الرقم الموحد', 'type' => 'text'],
            ['key' => 'tax_no', 'value' => 'الرقم الضريبي', 'type' => 'text'],
            ['key' => 'terms_title', 'value' => 'الشروط والأحكام', 'type' => 'text'],
            [
                // Replaces the previous wording with the exact text from the approved "NAQI – Simplified
                // Tax Invoice Redesign (Option B)" sample — 23 terms, same numbered order as terms_en below,
                // so item N here is the translation of item N there.
                'key' => 'terms',
                'value' => json_encode([
                    'يحق للعميل إلغاء الطلب في أي وقت قبل إتمام عملية التوصيل.',
                    'يحق للعميل صيانة المنتج من قبل فريق نقي خلال فترة الضمان.',
                    'يحق للعميل الاسترجاع أو الاستبدال خلال سبعة أيام من تاريخ الشراء على أن تكون المنتج بنفس حالته الأصلية عند شرائه، ولم يتم فكه أو تشغيله.',
                    'يحق للعميل استرجاع المنتج خلال يومين من تاريخ تركيب المنتج في حال وجود عطل مصنعي.',
                    'يحق للعميل الاستبدال خلال سبعة أيام من تاريخ تركيب المنتج في حال وجود عطل مصنعي.',
                    'يحق للعميل طلب استبدال أو استرجاع للمنتج في حال وجود مشكلة تعذّر إصلاحها من قبل فريق الصيانة.',
                    'ملاحظة: لا تتم عمليات الاسترجاع أو الاستبدال إلا بموجب الفاتورة الأصلية.',
                    'في حال استحقاق العميل استرجاع المبلغ، يتم الاسترجاع على الحساب البنكي الخاص بالعميل خلال 12 يوم عمل.',
                    'ملاحظة: يتم خصم قيمة الفلاتر الأولية من المنتجات التي تحتوي على فلاتر حسب نوعها وقيمتها كقيمة استهلاكية.',
                    'تضمن أجهزة التحلية الخاصة بنقي سنتان شامل القطع الكهربائية، و5 سنوات خدمة مجانية (زيارات وأجور يد).',
                    'يجب الالتزام بجدول الصيانة الدورية: تغيير فلاتر المراحل الأولى الثلاث كل ستة أشهر، والمرحلة الخامسة كل سنة، والمرحلة الرابعة كل سنتين.',
                    'يجب عدم تعريض القطع الكهربائية للمياه.',
                    'يجب عدم تعريض الجهاز لأي تعديل أو أعمال صيانة أو نقل من شركة أخرى.',
                    'يجب عدم تعريض الجهاز لأشعة الشمس والعوامل الجوية بشكل مباشر.',
                    'في حال الإخلال بأي بند من البنود الواردة يعتبر الضمان لاغياً.',
                    'ملاحظة: في حال وجود مشكلة في المنتج يجب على العميل رفع طلب صيانة عبر الرابط التالي: naqi.sa/complaints',
                    'يتم تركيب الجهاز مجاناً خلال شهر من تسليمه للعميل، وبعد مرور شهر من تاريخ الفاتورة يتم احتساب 100 ريال رسوم تركيب.',
                    'ضمان المنتجات المجددة الخاصة بنقي سنتان من تاريخ الشراء.',
                    'ملاحظة: قد يكون على المنتج المجدد آثار استعمال أو خدوش سطحية.',
                    'سيتم التواصل مع العميل خلال 5 أيام عمل لجدولة موعد تنفيذ الخدمة.',
                    'سيتم التواصل مع العميل للتذكير بالصيانات الدورية للمنتج.',
                    'يتحمل العميل رسوم الشحن عند إلغاء الطلب (35 ريال).',
                    'في حال كانت قيمة الفاتورة 499 ريال أو أقل، يتحمل العميل رسوم التوصيل بقيمة 35 ريال، غير شامل خدمات الصيانة.',
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
            ],
            [
                // English translation, directly from the same sample — item N here matches item N in
                // 'terms' above. No terms_en existed before this; invoice-v2 reads both for its two-column
                // bilingual layout.
                'key' => 'terms_en',
                'value' => json_encode([
                    'The customer may cancel the order at any time before delivery is completed.',
                    'The customer is entitled to product maintenance by the NAQI team during the warranty period.',
                    'Returns or exchanges are accepted within 7 days of purchase, provided the product is in its original condition, unused, not dismantled and not operated.',
                    'The customer may return the product within 2 days of installation in case of a manufacturing defect.',
                    'The customer may exchange the product within 7 days of installation in case of a manufacturing defect.',
                    'The customer may request an exchange or return if a fault cannot be repaired by the maintenance team.',
                    'Note: returns and exchanges are processed only against the original invoice.',
                    "Eligible refunds are paid to the customer's bank account within 12 working days.",
                    'Note: for products containing filters, the value of the initial filters is deducted as a consumable, according to type and value.',
                    'NAQI desalination units carry a 2-year warranty including electrical parts, plus 5 years of free service (visits and labour).',
                    'Follow the periodic maintenance schedule: replace stages 1–3 every 6 months, stage 5 every year, and stage 4 every 2 years.',
                    'Electrical parts must not be exposed to water.',
                    'The unit must not be modified, serviced or relocated by another company.',
                    'The unit must not be exposed directly to sunlight or weather.',
                    'Breach of any of these terms voids the warranty.',
                    'For any product issue, submit a maintenance request at naqi.sa/complaints',
                    'Installation is free within one month of delivery; after one month from the invoice date, a SAR 100 installation fee applies.',
                    'Refurbished NAQI products carry a 2-year warranty from the purchase date.',
                    'Note: refurbished products may show signs of use or surface scratches.',
                    'We will contact you within 5 working days to schedule the service.',
                    'We will contact you to remind you of periodic product maintenance.',
                    'A shipping fee of SAR 35 applies if the order is cancelled.',
                    'For invoices of SAR 499 or less, a SAR 35 delivery fee applies, excluding maintenance services.',
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
            ],
            ['key' => 'footer', 'value' => 'فاتورتك هي ضمانك، يرجى المحافظة عليها', 'type' => 'text'],
            [
                // address/city/unified_number updated to match the approved sample exactly (unified_number
                // did not exist here before — invoice-v2 is the first layout to show it).
                'key' => 'company',
                'value' => json_encode([
                    'name_ar'        => 'شركة النبع النقي للتجارة',
                    'name_en'        => 'Al Nabea Al Naqi Trading Company',
                    'tagline'        => 'محطات تحلية مياه - الرياض والقصيم',
                    'address'        => '6549 Wadi Al Shuara, Al Olaya',
                    'city'           => 'Riyadh 12211',
                    'country'        => 'Saudi Arabia',
                    'phone'          => '920021500',
                    'email'          => 'contact@naqi.sa',
                    'vat_no'         => '310362058800003',
                    'unified_number' => '920021500',
                ], JSON_UNESCAPED_UNICODE),
                'type' => 'json',
            ],
        ];

        foreach ($settings as $setting) {
            InvoiceSetting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type'  => $setting['type'],
                ]
            );
        }
    }
}
