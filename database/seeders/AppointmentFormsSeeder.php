<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentType;
use App\Models\FormField;
use App\Models\AppointmentTypeOption;
use App\Models\OptionField;

class AppointmentFormsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1) Appointment Types
            $types = [
                [
                    'code' => 'periodic',
                    'name_ar' => 'مواعيد الدورية',
                    'name_en' => 'Periodic Appointments'
                ],
                [
                    'code' => 'service',
                    'name_ar' => 'مواعيد الخدمات',
                    'name_en' => 'Service Appointments'
                ],
                [
                    'code' => 'emergency',
                    'name_ar' => 'مواعيد الطارئة',
                    'name_en' => 'Emergency Appointments'
                ]
            ];

            $typeIdByCode = [];
            foreach ($types as $t) {
                $type = AppointmentType::updateOrCreate(
                    ['code' => $t['code']],
                    [
                        'name_ar' => $t['name_ar'],
                        'name_en' => $t['name_en'],
                        'is_active' => true,
                    ]
                );
                $typeIdByCode[$t['code']] = $type->id;
            }

            // 2) Form Fields
            $fields = [
                [
                    'field_key' => 'fld_0c6804d6_c34',
                    'label_ar' => 'قياس درجة حرارة الماء',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_0c6804d6_c56',
                    'label_ar' => 'قياس درجة حرارة الماء',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_1f63a129_c40',
                    'label_ar' => 'فوق المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_1f63a129_c62',
                    'label_ar' => 'فوق المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_2678cebc_c19',
                    'label_ar' => 'المنتج عند التسليم',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_32b8012d_c39',
                    'label_ar' => 'يسار المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_32b8012d_c61',
                    'label_ar' => 'يسار المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_335ed8e0_c17',
                    'label_ar' => 'نظافة المجلى و الموقع',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_335ed8e0_c26',
                    'label_ar' => 'نظافة المجلى و الموقع',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_335ed8e0_c50',
                    'label_ar' => 'نظافة المجلى و الموقع',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_572958e4_c29',
                    'label_ar' => 'المنتج بعد فك الجهاز',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_572958e4_c53',
                    'label_ar' => 'المنتج بعد فك الجهاز',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_59e829d5_c33',
                    'label_ar' => 'قياس الكهرباء',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_59e829d5_c55',
                    'label_ar' => 'قياس الكهرباء',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_82302d62_c14',
                    'label_ar' => 'نسبة الأملاح في مياه منزل العميل',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_82302d62_c23',
                    'label_ar' => 'نسبة الأملاح في مياه منزل العميل',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_82302d62_c47',
                    'label_ar' => 'نسبة الأملاح في مياه منزل العميل',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_8460f515_c16',
                    'label_ar' => 'تفريغ الكربون',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_8460f515_c25',
                    'label_ar' => 'تفريغ الكربون',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_8460f515_c49',
                    'label_ar' => 'تفريغ الكربون',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_93c3603a_c37',
                    'label_ar' => 'يمين المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_93c3603a_c41',
                    'label_ar' => 'يمين المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_93c3603a_c60',
                    'label_ar' => 'يمين المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_a82e6360_c30',
                    'label_ar' => 'تمديد',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_a82e6360_c31',
                    'label_ar' => 'تمديد',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_a82e6360_c32',
                    'label_ar' => 'تمديد',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_a82e6360_c54',
                    'label_ar' => 'تمديد',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_af2c1f5f_c35',
                    'label_ar' => 'نموذج طلب صيانة بعد اضافة البيانات و توقيع العميل',
                    'label_en' => null,
                    'field_type' => 'file'
                ],
                [
                    'field_key' => 'fld_af2c1f5f_c57',
                    'label_ar' => 'نموذج طلب صيانة بعد اضافة البيانات و توقيع العميل',
                    'label_en' => null,
                    'field_type' => 'file'
                ],
                [
                    'field_key' => 'fld_d3bc3731_c13',
                    'label_ar' => 'ملاحظة أخرى',
                    'label_en' => null,
                    'field_type' => 'textarea'
                ],
                [
                    'field_key' => 'fld_d3bc3731_c22',
                    'label_ar' => 'ملاحظة أخرى',
                    'label_en' => null,
                    'field_type' => 'textarea'
                ],
                [
                    'field_key' => 'fld_d3bc3731_c46',
                    'label_ar' => 'ملاحظة أخرى',
                    'label_en' => null,
                    'field_type' => 'textarea'
                ],
                [
                    'field_key' => 'fld_ddbe24e9_c18',
                    'label_ar' => 'ربط لي التصريف',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_ddbe24e9_c27',
                    'label_ar' => 'ربط لي التصريف',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_ddbe24e9_c51',
                    'label_ar' => 'ربط لي التصريف',
                    'label_en' => null,
                    'field_type' => 'boolean'
                ],
                [
                    'field_key' => 'fld_debb040b_c36',
                    'label_ar' => 'أمام المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_debb040b_c58',
                    'label_ar' => 'أمام المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_e1c16112_c15',
                    'label_ar' => 'نسبة أملاح الجهاز بعد الخدمة',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_e1c16112_c24',
                    'label_ar' => 'نسبة أملاح الجهاز بعد الخدمة',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_e1c16112_c48',
                    'label_ar' => 'نسبة أملاح الجهاز بعد الخدمة',
                    'label_en' => null,
                    'field_type' => 'number'
                ],
                [
                    'field_key' => 'fld_ea6f24ae_c38',
                    'label_ar' => 'خلف المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_ea6f24ae_c59',
                    'label_ar' => 'خلف المنتج',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_f8000877_c28',
                    'label_ar' => 'المنتج بعد التركيب',
                    'label_en' => null,
                    'field_type' => 'image'
                ],
                [
                    'field_key' => 'fld_f8000877_c52',
                    'label_ar' => 'المنتج بعد التركيب',
                    'label_en' => null,
                    'field_type' => 'image'
                ]
            ];

            $fieldIdByKey = [];
            foreach ($fields as $f) {
                $field = FormField::updateOrCreate(
                    ['field_key' => $f['field_key']],
                    [
                        'label_ar' => $f['label_ar'],
                        'label_en' => $f['label_en'],
                        'field_type' => $f['field_type'],
                        'is_active' => true,
                    ]
                );
                $fieldIdByKey[$f['field_key']] = $field->id;
            }

            // 3) Options (flat for periodic/service + hierarchical for emergency)
            $options = [
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'label_en' => null,
                    'sort_order' => 2
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'label_en' => null,
                    'sort_order' => 3
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'label_en' => null,
                    'sort_order' => 4
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'label_en' => null,
                    'sort_order' => 5
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'label_en' => null,
                    'sort_order' => 6
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'label_en' => null,
                    'sort_order' => 7
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'label_en' => null,
                    'sort_order' => 8
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'label_en' => null,
                    'sort_order' => 9
                ],
                [
                    'type_code' => 'periodic',
                    'key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'parent_key' => null,
                    'label_ar' => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'label_en' => null,
                    'sort_order' => 10
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'label_en' => null,
                    'sort_order' => 2
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'label_en' => null,
                    'sort_order' => 3
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و فك الجهاز',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و فك الجهاز',
                    'label_en' => null,
                    'sort_order' => 4
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و تركيب الجهاز',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و تركيب الجهاز',
                    'label_en' => null,
                    'sort_order' => 5
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و فك و تركيب الجهاز',
                    'label_en' => null,
                    'sort_order' => 6
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و تمديد قارورة او غطاء برادة',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و تمديد قارورة او غطاء برادة',
                    'label_en' => null,
                    'sort_order' => 7
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و تسليم المنتج للعميل',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و تسليم المنتج للعميل',
                    'label_en' => null,
                    'sort_order' => 8
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و سحب المنتج من العميل',
                    'label_en' => null,
                    'sort_order' => 9
                ],
                [
                    'type_code' => 'service',
                    'key' => 'opt::service::تم خدمة العميل و توفير  القطعة',
                    'parent_key' => null,
                    'label_ar' => 'تم خدمة العميل و توفير  القطعة',
                    'label_en' => null,
                    'sort_order' => 10
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::برادات ذاتيه التعبئة',
                    'parent_key' => null,
                    'label_ar' => 'برادات ذاتيه التعبئة',
                    'label_en' => null,
                    'sort_order' => 2
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::البارد لا يعمل',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'البارد لا يعمل',
                    'label_en' => null,
                    'sort_order' => 2
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::البارد لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 2
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::البارد لا يعمل',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 3
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::البارد لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 4
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::التماس',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'التماس',
                    'label_en' => null,
                    'sort_order' => 5
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::التماس::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::التماس',
                    'label_ar' => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'label_en' => null,
                    'sort_order' => 5
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::التماس',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 6
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::الحار لا يعمل',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'الحار لا يعمل',
                    'label_en' => null,
                    'sort_order' => 7
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الحار لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 7
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الحار لا يعمل',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 8
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الحار لا يعمل',
                    'label_ar' => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'label_en' => null,
                    'sort_order' => 9
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الحار لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 10
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::الفاتر يطلع بارد',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'الفاتر يطلع بارد',
                    'label_en' => null,
                    'sort_order' => 11
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الفاتر يطلع بارد::تم ضبط الفاصل الداخلي للخزان',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الفاتر يطلع بارد',
                    'label_ar' => 'تم ضبط الفاصل الداخلي للخزان',
                    'label_en' => null,
                    'sort_order' => 11
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'الكمبروسر يعمل باستمرار',
                    'label_en' => null,
                    'sort_order' => 12
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 12
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 13
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 14
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::المنتج لا يعمل',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 15
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم توصيل مصدر المياه للمنتج',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::المنتج لا يعمل',
                    'label_ar' => 'تم توصيل مصدر المياه للمنتج',
                    'label_en' => null,
                    'sort_order' => 15
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::المنتج لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 16
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::المنتج لا يعمل',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 17
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::المنتج لا يعمل',
                    'label_ar' => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'label_en' => null,
                    'sort_order' => 18
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 19
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::تهريب',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 20
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::تهريب',
                    'label_ar' => 'تمت معالجة مشكلة التسريب بالكامل',
                    'label_en' => null,
                    'sort_order' => 20
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم ضبط درجة البرودة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::تهريب',
                    'label_ar' => 'تم ضبط درجة البرودة',
                    'label_en' => null,
                    'sort_order' => 21
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 22
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::صوت مزعج',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 23
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 23
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::طعم و شوائب',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'طعم و شوائب',
                    'label_en' => null,
                    'sort_order' => 24
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::طعم و شوائب',
                    'label_ar' => 'تم تفريغ الماء في الخزان',
                    'label_en' => null,
                    'sort_order' => 24
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::كسر في الديكور',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'كسر في الديكور',
                    'label_en' => null,
                    'sort_order' => 25
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::كسر في الديكور',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 25
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::برادات ذاتيه التعبئة',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 26
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 26
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 27
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::تحلية',
                    'parent_key' => null,
                    'label_ar' => 'تحلية',
                    'label_en' => null,
                    'sort_order' => 28
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تحلية::الفلتر لا يعمل',
                    'parent_key' => 'dev::تحلية',
                    'label_ar' => 'الفلتر لا يعمل',
                    'label_en' => null,
                    'sort_order' => 28
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 28
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 29
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل أسلاك الحساسات بشكل صحيح',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم توصيل أسلاك الحساسات بشكل صحيح',
                    'label_en' => null,
                    'sort_order' => 30
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم تنظيف مصدر الماء',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم تنظيف مصدر الماء',
                    'label_en' => null,
                    'sort_order' => 31
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس خارج الضمان',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال الحساس خارج الضمان',
                    'label_en' => null,
                    'sort_order' => 32
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة خارج الضمان',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال المضخة خارج الضمان',
                    'label_en' => null,
                    'sort_order' => 33
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول خارج الضمان',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال المحول خارج الضمان',
                    'label_en' => null,
                    'sort_order' => 34
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس ضمن الضمان',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال الحساس ضمن الضمان',
                    'label_en' => null,
                    'sort_order' => 35
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة ضمن الضمان',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال المضخة ضمن الضمان',
                    'label_en' => null,
                    'sort_order' => 36
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول ضمن الضمان',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال المحول ضمن الضمان',
                    'label_en' => null,
                    'sort_order' => 37
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال قطع الغيار اللازمة',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم استبدال قطع الغيار اللازمة',
                    'label_en' => null,
                    'sort_order' => 38
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::الفلتر لا يعمل::تم تغيير فلاتر',
                    'parent_key' => 'prob::تحلية::الفلتر لا يعمل',
                    'label_ar' => 'تم تغيير فلاتر',
                    'label_en' => null,
                    'sort_order' => 39
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تحلية::تصريف مستمر',
                    'parent_key' => 'dev::تحلية',
                    'label_ar' => 'تصريف مستمر',
                    'label_en' => null,
                    'sort_order' => 40
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'parent_key' => 'prob::تحلية::تصريف مستمر',
                    'label_ar' => 'تم تغيير حساس خارج الضمان',
                    'label_en' => null,
                    'sort_order' => 40
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'parent_key' => 'prob::تحلية::تصريف مستمر',
                    'label_ar' => 'تم استبدال الحساس ضمن الضمان',
                    'label_en' => null,
                    'sort_order' => 41
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'parent_key' => 'prob::تحلية::تصريف مستمر',
                    'label_ar' => 'تمت معالجة مشكلة التسريب بالكامل',
                    'label_en' => null,
                    'sort_order' => 42
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'parent_key' => 'prob::تحلية::تصريف مستمر',
                    'label_ar' => 'تم استبدال قطع الغيار اللازمة',
                    'label_en' => null,
                    'sort_order' => 43
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تحلية::تهريب',
                    'parent_key' => 'dev::تحلية',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 44
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تهريب::تم تغيير الهاوس',
                    'parent_key' => 'prob::تحلية::تهريب',
                    'label_ar' => 'تم تغيير الهاوس',
                    'label_en' => null,
                    'sort_order' => 44
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'parent_key' => 'prob::تحلية::تهريب',
                    'label_ar' => 'تمت معالجة مشكلة التسريب بالكامل',
                    'label_en' => null,
                    'sort_order' => 45
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تهريب::تم استبدال قطع الغيار اللازمة',
                    'parent_key' => 'prob::تحلية::تهريب',
                    'label_ar' => 'تم استبدال قطع الغيار اللازمة',
                    'label_en' => null,
                    'sort_order' => 46
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::تهريب::التهريب من مجلى العميل',
                    'parent_key' => 'prob::تحلية::تهريب',
                    'label_ar' => 'التهريب من مجلى العميل',
                    'label_en' => null,
                    'sort_order' => 47
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تحلية::طعم و شوائب',
                    'parent_key' => 'dev::تحلية',
                    'label_ar' => 'طعم و شوائب',
                    'label_en' => null,
                    'sort_order' => 48
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::طعم و شوائب::تم تنظيف البوستات',
                    'parent_key' => 'prob::تحلية::طعم و شوائب',
                    'label_ar' => 'تم تنظيف البوستات',
                    'label_en' => null,
                    'sort_order' => 48
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'parent_key' => 'prob::تحلية::طعم و شوائب',
                    'label_ar' => 'تم استبدال البوستات ضمن الضمان',
                    'label_en' => null,
                    'sort_order' => 49
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::طعم و شوائب::تم تغيير فلاتر',
                    'parent_key' => 'prob::تحلية::طعم و شوائب',
                    'label_ar' => 'تم تغيير فلاتر',
                    'label_en' => null,
                    'sort_order' => 50
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'parent_key' => 'prob::تحلية::طعم و شوائب',
                    'label_ar' => 'تم ضبط نسبة الأملاح',
                    'label_en' => null,
                    'sort_order' => 51
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'parent_key' => 'prob::تحلية::طعم و شوائب',
                    'label_ar' => 'تم تفريغ الماء في الخزان',
                    'label_en' => null,
                    'sort_order' => 52
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تحلية::قارورة البرادة تهرب',
                    'parent_key' => 'dev::تحلية',
                    'label_ar' => 'قارورة البرادة تهرب',
                    'label_en' => null,
                    'sort_order' => 53
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::قارورة البرادة تهرب::تم ضبط العوامة',
                    'parent_key' => 'prob::تحلية::قارورة البرادة تهرب',
                    'label_ar' => 'تم ضبط العوامة',
                    'label_en' => null,
                    'sort_order' => 53
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::قارورة البرادة تهرب::تم استبدال قطع الغيار اللازمة',
                    'parent_key' => 'prob::تحلية::قارورة البرادة تهرب',
                    'label_ar' => 'تم استبدال قطع الغيار اللازمة',
                    'label_en' => null,
                    'sort_order' => 54
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::قارورة البرادة تهرب::تمت معالجة مشكلة التسريب بالكامل',
                    'parent_key' => 'prob::تحلية::قارورة البرادة تهرب',
                    'label_ar' => 'تمت معالجة مشكلة التسريب بالكامل',
                    'label_en' => null,
                    'sort_order' => 55
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::تحلية',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 56
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 56
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 57
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::برادة فلتر داخلي',
                    'parent_key' => null,
                    'label_ar' => 'برادة فلتر داخلي',
                    'label_en' => null,
                    'sort_order' => 58
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::البارد لا يعمل',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'البارد لا يعمل',
                    'label_en' => null,
                    'sort_order' => 58
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادة فلتر داخلي::البارد لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 58
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادة فلتر داخلي::البارد لا يعمل',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 59
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::البارد لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 60
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::التماس',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'التماس',
                    'label_en' => null,
                    'sort_order' => 61
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::التماس::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'parent_key' => 'prob::برادة فلتر داخلي::التماس',
                    'label_ar' => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'label_en' => null,
                    'sort_order' => 61
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::التماس',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 62
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::الحار لا يعمل',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'الحار لا يعمل',
                    'label_en' => null,
                    'sort_order' => 63
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادة فلتر داخلي::الحار لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 63
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادة فلتر داخلي::الحار لا يعمل',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 64
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'parent_key' => 'prob::برادة فلتر داخلي::الحار لا يعمل',
                    'label_ar' => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'label_en' => null,
                    'sort_order' => 65
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::الحار لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 66
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::الفاتر يطلع بارد',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'الفاتر يطلع بارد',
                    'label_en' => null,
                    'sort_order' => 67
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الفاتر يطلع بارد::تم ضبط الفاصل الداخلي للخزان',
                    'parent_key' => 'prob::برادة فلتر داخلي::الفاتر يطلع بارد',
                    'label_ar' => 'تم ضبط الفاصل الداخلي للخزان',
                    'label_en' => null,
                    'sort_order' => 67
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::الكمبروسر يعمل باستمرار',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'الكمبروسر يعمل باستمرار',
                    'label_en' => null,
                    'sort_order' => 68
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادة فلتر داخلي::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 68
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادة فلتر داخلي::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 69
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 70
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::المنتج لا يعمل',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 71
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم توصيل مصدر المياه للمنتج',
                    'parent_key' => 'prob::برادة فلتر داخلي::المنتج لا يعمل',
                    'label_ar' => 'تم توصيل مصدر المياه للمنتج',
                    'label_en' => null,
                    'sort_order' => 71
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::برادة فلتر داخلي::المنتج لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 72
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم استبدال فيوز الفيش',
                    'parent_key' => 'prob::برادة فلتر داخلي::المنتج لا يعمل',
                    'label_ar' => 'تم استبدال فيوز الفيش',
                    'label_en' => null,
                    'sort_order' => 73
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'parent_key' => 'prob::برادة فلتر داخلي::المنتج لا يعمل',
                    'label_ar' => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'label_en' => null,
                    'sort_order' => 74
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 75
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::تصريف مستمر',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'تصريف مستمر',
                    'label_en' => null,
                    'sort_order' => 76
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'parent_key' => 'prob::برادة فلتر داخلي::تصريف مستمر',
                    'label_ar' => 'تم تغيير حساس خارج الضمان',
                    'label_en' => null,
                    'sort_order' => 76
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'parent_key' => 'prob::برادة فلتر داخلي::تصريف مستمر',
                    'label_ar' => 'تم استبدال الحساس ضمن الضمان',
                    'label_en' => null,
                    'sort_order' => 77
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'parent_key' => 'prob::برادة فلتر داخلي::تصريف مستمر',
                    'label_ar' => 'تمت معالجة مشكلة التسريب بالكامل',
                    'label_en' => null,
                    'sort_order' => 78
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'parent_key' => 'prob::برادة فلتر داخلي::تصريف مستمر',
                    'label_ar' => 'تم استبدال قطع الغيار اللازمة',
                    'label_en' => null,
                    'sort_order' => 79
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::تهريب',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 80
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تهريب::تم تغيير الهاوس',
                    'parent_key' => 'prob::برادة فلتر داخلي::تهريب',
                    'label_ar' => 'تم تغيير الهاوس',
                    'label_en' => null,
                    'sort_order' => 80
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'parent_key' => 'prob::برادة فلتر داخلي::تهريب',
                    'label_ar' => 'تمت معالجة مشكلة التسريب بالكامل',
                    'label_en' => null,
                    'sort_order' => 81
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تهريب::تم استبدال قطع الغيار اللازمة',
                    'parent_key' => 'prob::برادة فلتر داخلي::تهريب',
                    'label_ar' => 'تم استبدال قطع الغيار اللازمة',
                    'label_en' => null,
                    'sort_order' => 82
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تهريب::التهريب من مجلى العميل',
                    'parent_key' => 'prob::برادة فلتر داخلي::تهريب',
                    'label_ar' => 'التهريب من مجلى العميل',
                    'label_en' => null,
                    'sort_order' => 83
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 84
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::صوت مزعج',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 85
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 85
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم تثبيت الليات وإحكام ربطها',
                    'parent_key' => 'prob::برادة فلتر داخلي::صوت مزعج',
                    'label_ar' => 'تم تثبيت الليات وإحكام ربطها',
                    'label_en' => null,
                    'sort_order' => 86
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::توفير قطعة',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'توفير قطعة',
                    'label_en' => null,
                    'sort_order' => 87
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::توفير قطعة::تم تسليم القطعة للعميل',
                    'parent_key' => 'prob::برادة فلتر داخلي::توفير قطعة',
                    'label_ar' => 'تم تسليم القطعة للعميل',
                    'label_en' => null,
                    'sort_order' => 87
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::طعم و شوائب',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'طعم و شوائب',
                    'label_en' => null,
                    'sort_order' => 88
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تنظيف البوستات',
                    'parent_key' => 'prob::برادة فلتر داخلي::طعم و شوائب',
                    'label_ar' => 'تم تنظيف البوستات',
                    'label_en' => null,
                    'sort_order' => 88
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'parent_key' => 'prob::برادة فلتر داخلي::طعم و شوائب',
                    'label_ar' => 'تم استبدال البوستات ضمن الضمان',
                    'label_en' => null,
                    'sort_order' => 89
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تغيير فلاتر',
                    'parent_key' => 'prob::برادة فلتر داخلي::طعم و شوائب',
                    'label_ar' => 'تم تغيير فلاتر',
                    'label_en' => null,
                    'sort_order' => 90
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'parent_key' => 'prob::برادة فلتر داخلي::طعم و شوائب',
                    'label_ar' => 'تم ضبط نسبة الأملاح',
                    'label_en' => null,
                    'sort_order' => 91
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'parent_key' => 'prob::برادة فلتر داخلي::طعم و شوائب',
                    'label_ar' => 'تم تفريغ الماء في الخزان',
                    'label_en' => null,
                    'sort_order' => 92
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::كسر في الديكور',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'كسر في الديكور',
                    'label_en' => null,
                    'sort_order' => 93
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::برادة فلتر داخلي::كسر في الديكور',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 93
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::برادة فلتر داخلي',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 94
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 94
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 95
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::تدوير الطعام',
                    'parent_key' => null,
                    'label_ar' => 'تدوير الطعام',
                    'label_en' => null,
                    'sort_order' => 96
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تدوير الطعام::المنتج لا يعمل',
                    'parent_key' => 'dev::تدوير الطعام',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 96
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::تدوير الطعام::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 96
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تدوير الطعام::تهريب',
                    'parent_key' => 'dev::تدوير الطعام',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 97
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تدوير الطعام::تهريب::تم تسليم القطعة للعميل',
                    'parent_key' => 'prob::تدوير الطعام::تهريب',
                    'label_ar' => 'تم تسليم القطعة للعميل',
                    'label_en' => null,
                    'sort_order' => 97
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تدوير الطعام::صوت مزعج',
                    'parent_key' => 'dev::تدوير الطعام',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 98
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::تدوير الطعام::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 98
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تدوير الطعام::كسر في الخلاط',
                    'parent_key' => 'dev::تدوير الطعام',
                    'label_ar' => 'كسر في الخلاط',
                    'label_en' => null,
                    'sort_order' => 99
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::تدوير الطعام::كسر في الخلاط',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 99
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::تدوير الطعام',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 100
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 100
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 101
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::خلاطات',
                    'parent_key' => null,
                    'label_ar' => 'خلاطات',
                    'label_en' => null,
                    'sort_order' => 102
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::خلاطات::المنتج لا يعمل',
                    'parent_key' => 'dev::خلاطات',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 102
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::خلاطات::المنتج لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 102
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::خلاطات::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 103
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::خلاطات::تهريب',
                    'parent_key' => 'dev::خلاطات',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 104
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::خلاطات::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 104
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::خلاطات::توفير قطعة',
                    'parent_key' => 'dev::خلاطات',
                    'label_ar' => 'توفير قطعة',
                    'label_en' => null,
                    'sort_order' => 105
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::توفير قطعة::تم تسليم القطعة للعميل',
                    'parent_key' => 'prob::خلاطات::توفير قطعة',
                    'label_ar' => 'تم تسليم القطعة للعميل',
                    'label_en' => null,
                    'sort_order' => 105
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::خلاطات::صوت مزعج',
                    'parent_key' => 'dev::خلاطات',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 106
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::خلاطات::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 106
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::خلاطات::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::خلاطات',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 107
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::خلاطات::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 107
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::خلاطات',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 108
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 108
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 109
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::دفايات',
                    'parent_key' => null,
                    'label_ar' => 'دفايات',
                    'label_en' => null,
                    'sort_order' => 110
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::الاضاه معطله',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'الاضاه معطله',
                    'label_en' => null,
                    'sort_order' => 110
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::دفايات::الاضاه معطله',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 110
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::التسخين لا يعمل',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'التسخين لا يعمل',
                    'label_en' => null,
                    'sort_order' => 111
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::دفايات::التسخين لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 111
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::المنتج لا يعمل',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 112
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::دفايات::المنتج لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 112
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::دفايات::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 113
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::تلف في سلك التوصيل الكهربائي',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'تلف في سلك التوصيل الكهربائي',
                    'label_en' => null,
                    'sort_order' => 114
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::دفايات::تلف في سلك التوصيل الكهربائي',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 114
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::توفير قطعة',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'توفير قطعة',
                    'label_en' => null,
                    'sort_order' => 115
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::توفير قطعة::تم تسليم القطعة للعميل',
                    'parent_key' => 'prob::دفايات::توفير قطعة',
                    'label_ar' => 'تم تسليم القطعة للعميل',
                    'label_en' => null,
                    'sort_order' => 115
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::كسر في أنبوب التسخين',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'كسر في أنبوب التسخين',
                    'label_en' => null,
                    'sort_order' => 116
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::دفايات::كسر في أنبوب التسخين',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 116
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::كسر في الديكور',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'كسر في الديكور',
                    'label_en' => null,
                    'sort_order' => 117
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::دفايات::كسر في الديكور',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 117
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::دفايات',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 118
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 118
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 119
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::صانعه الايسكريم',
                    'parent_key' => null,
                    'label_ar' => 'صانعه الايسكريم',
                    'label_en' => null,
                    'sort_order' => 120
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الايسكريم::المنتج لا يعمل',
                    'parent_key' => 'dev::صانعه الايسكريم',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 120
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الايسكريم::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 120
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'parent_key' => 'prob::صانعه الايسكريم::المنتج لا يعمل',
                    'label_ar' => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                    'label_en' => null,
                    'sort_order' => 121
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الايسكريم::تهريب',
                    'parent_key' => 'dev::صانعه الايسكريم',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 122
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'parent_key' => 'prob::صانعه الايسكريم::تهريب',
                    'label_ar' => 'تمت معالجة مشكلة التسريب بالكامل',
                    'label_en' => null,
                    'sort_order' => 122
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الايسكريم::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 123
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الايسكريم::توفير قطعة',
                    'parent_key' => 'dev::صانعه الايسكريم',
                    'label_ar' => 'توفير قطعة',
                    'label_en' => null,
                    'sort_order' => 124
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::توفير قطعة::تم تسليم القطعة للعميل',
                    'parent_key' => 'prob::صانعه الايسكريم::توفير قطعة',
                    'label_ar' => 'تم تسليم القطعة للعميل',
                    'label_en' => null,
                    'sort_order' => 124
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الايسكريم::كسر في الواجهه',
                    'parent_key' => 'dev::صانعه الايسكريم',
                    'label_ar' => 'كسر في الواجهه',
                    'label_en' => null,
                    'sort_order' => 125
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الايسكريم::كسر في الواجهه',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 125
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الايسكريم::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::صانعه الايسكريم',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 126
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الايسكريم::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 126
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::صانعه الايسكريم',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 127
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 127
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 128
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::صانعه الثلج',
                    'parent_key' => null,
                    'label_ar' => 'صانعه الثلج',
                    'label_en' => null,
                    'sort_order' => 129
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::الانتاجيه ضعيفه',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'الانتاجيه ضعيفه',
                    'label_en' => null,
                    'sort_order' => 129
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الثلج::الانتاجيه ضعيفه',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 129
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::المنتج لا يعمل',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 130
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الثلج::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 130
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::تهريب',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 131
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الثلج::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 131
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::صدا في الكويل',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'صدا في الكويل',
                    'label_en' => null,
                    'sort_order' => 132
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الثلج::صدا في الكويل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 132
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::صوت مزعج',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 133
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الثلج::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 133
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::طعم و شوائب',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'طعم و شوائب',
                    'label_en' => null,
                    'sort_order' => 134
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الثلج::طعم و شوائب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 134
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 135
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صانعه الثلج::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 135
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::صانعه الثلج',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 136
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 136
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 137
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::صناديق التبريد',
                    'parent_key' => null,
                    'label_ar' => 'صناديق التبريد',
                    'label_en' => null,
                    'sort_order' => 138
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::الكمبروسر يعمل باستمرار',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'الكمبروسر يعمل باستمرار',
                    'label_en' => null,
                    'sort_order' => 138
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صناديق التبريد::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 138
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::المنتج لا يعمل',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 139
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صناديق التبريد::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 139
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::تبريد ضعيف',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'تبريد ضعيف',
                    'label_en' => null,
                    'sort_order' => 140
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صناديق التبريد::تبريد ضعيف',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 140
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::تسخين مستمر',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'تسخين مستمر',
                    'label_en' => null,
                    'sort_order' => 141
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صناديق التبريد::تسخين مستمر',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 141
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::عطل في سلك توصيل السياره',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'عطل في سلك توصيل السياره',
                    'label_en' => null,
                    'sort_order' => 142
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صناديق التبريد::عطل في سلك توصيل السياره',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 142
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::عطل في لوحه التحكم',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'عطل في لوحه التحكم',
                    'label_en' => null,
                    'sort_order' => 143
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صناديق التبريد::عطل في لوحه التحكم',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 143
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::كسر في الديكور',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'كسر في الديكور',
                    'label_en' => null,
                    'sort_order' => 144
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::صناديق التبريد::كسر في الديكور',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 144
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::صناديق التبريد',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 145
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 145
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 146
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::فلاتر الشاور والصنابير',
                    'parent_key' => null,
                    'label_ar' => 'فلاتر الشاور والصنابير',
                    'label_en' => null,
                    'sort_order' => 147
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::فلاتر الشاور والصنابير::المنتج لا يعمل',
                    'parent_key' => 'dev::فلاتر الشاور والصنابير',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 147
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::فلاتر الشاور والصنابير::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 147
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::فلاتر الشاور والصنابير::تهريب',
                    'parent_key' => 'dev::فلاتر الشاور والصنابير',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 148
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::فلاتر الشاور والصنابير::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 148
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::فلاتر الشاور والصنابير::توفير قطعة',
                    'parent_key' => 'dev::فلاتر الشاور والصنابير',
                    'label_ar' => 'توفير قطعة',
                    'label_en' => null,
                    'sort_order' => 149
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::فلاتر الشاور والصنابير::توفير قطعة',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 149
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::فلاتر الشاور والصنابير::صوت مزعج',
                    'parent_key' => 'dev::فلاتر الشاور والصنابير',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 150
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::فلاتر الشاور والصنابير::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 150
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::فلاتر الشاور والصنابير',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 151
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 151
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::مشروبات ساخنة',
                    'parent_key' => null,
                    'label_ar' => 'مشروبات ساخنة',
                    'label_en' => null,
                    'sort_order' => 152
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::الحار لا يعمل',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'الحار لا يعمل',
                    'label_en' => null,
                    'sort_order' => 152
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مشروبات ساخنة::الحار لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 152
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::المنتج لا يعمل',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 153
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مشروبات ساخنة::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 153
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::تسخين مستمر',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'تسخين مستمر',
                    'label_en' => null,
                    'sort_order' => 154
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مشروبات ساخنة::تسخين مستمر',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 154
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::تهريب',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 155
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مشروبات ساخنة::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 155
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::توفير قطعة',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'توفير قطعة',
                    'label_en' => null,
                    'sort_order' => 156
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مشروبات ساخنة::توفير قطعة',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 156
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::كسر في خزان الماء',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'كسر في خزان الماء',
                    'label_en' => null,
                    'sort_order' => 157
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مشروبات ساخنة::كسر في خزان الماء',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 157
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 158
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مشروبات ساخنة::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 158
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::مشروبات ساخنة',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 159
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 159
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 160
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::مضخات',
                    'parent_key' => null,
                    'label_ar' => 'مضخات',
                    'label_en' => null,
                    'sort_order' => 161
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مضخات::المنتج لا يعمل',
                    'parent_key' => 'dev::مضخات',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 161
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مضخات::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 161
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مضخات::تهريب',
                    'parent_key' => 'dev::مضخات',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 162
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مضخات::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 162
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مضخات::صوت مزعج',
                    'parent_key' => 'dev::مضخات',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 163
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مضخات::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 163
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مضخات::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::مضخات',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 164
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مضخات::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 164
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مضخات::يعمل باستمرار',
                    'parent_key' => 'dev::مضخات',
                    'label_ar' => 'يعمل باستمرار',
                    'label_en' => null,
                    'sort_order' => 165
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::مضخات::يعمل باستمرار',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 165
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::مضخات',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 166
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 166
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 167
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::منقيات الهواء',
                    'parent_key' => null,
                    'label_ar' => 'منقيات الهواء',
                    'label_en' => null,
                    'sort_order' => 168
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::الاسلاش لا يعمل',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'الاسلاش لا يعمل',
                    'label_en' => null,
                    'sort_order' => 168
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::الاسلاش لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 168
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::المنتج لا يعمل',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 169
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 169
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::الموقت لا يعمل',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'الموقت لا يعمل',
                    'label_en' => null,
                    'sort_order' => 170
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::الموقت لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 170
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::انسداد في النزل',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'انسداد في النزل',
                    'label_en' => null,
                    'sort_order' => 171
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::انسداد في النزل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 171
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::تصريف مستمر',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'تصريف مستمر',
                    'label_en' => null,
                    'sort_order' => 172
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::تصريف مستمر',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 172
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::تهريب',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'تهريب',
                    'label_en' => null,
                    'sort_order' => 173
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::تهريب',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 173
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::توفير قطعة',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'توفير قطعة',
                    'label_en' => null,
                    'sort_order' => 174
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::توفير قطعة',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 174
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::دفع الهواء ضعيف',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'دفع الهواء ضعيف',
                    'label_en' => null,
                    'sort_order' => 175
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::دفع الهواء ضعيف',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 175
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::رائحه سيئه',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'رائحه سيئه',
                    'label_en' => null,
                    'sort_order' => 176
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::رائحه سيئه',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 176
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::صوت مزعج',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'صوت مزعج',
                    'label_en' => null,
                    'sort_order' => 177
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::صوت مزعج',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 177
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::كسر في الديكور',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'كسر في الديكور',
                    'label_en' => null,
                    'sort_order' => 178
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::كسر في الديكور',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 178
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::كسر في الفلتر',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'كسر في الفلتر',
                    'label_en' => null,
                    'sort_order' => 179
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::كسر في الفلتر',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 179
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::كسر في الواجهه الزجاجيه',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'كسر في الواجهه الزجاجيه',
                    'label_en' => null,
                    'sort_order' => 180
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::كسر في الواجهه الزجاجيه',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 180
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::كسر في خزان الماء',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'كسر في خزان الماء',
                    'label_en' => null,
                    'sort_order' => 181
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::كسر في خزان الماء',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 181
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::لا يفلتر الهواء',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'لا يفلتر الهواء',
                    'label_en' => null,
                    'sort_order' => 182
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::لا يفلتر الهواء',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 182
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 183
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 183
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::نسداد في النزل',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'نسداد في النزل',
                    'label_en' => null,
                    'sort_order' => 184
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::نسداد في النزل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 184
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::يعمل باستمرار',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'يعمل باستمرار',
                    'label_en' => null,
                    'sort_order' => 185
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::منقيات الهواء::يعمل باستمرار',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 185
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::منقيات الهواء',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 186
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 186
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 187
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'dev::واجهات تبريد',
                    'parent_key' => null,
                    'label_ar' => 'واجهات تبريد',
                    'label_en' => null,
                    'sort_order' => 188
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::واجهات تبريد::الاناره لا تعمل',
                    'parent_key' => 'dev::واجهات تبريد',
                    'label_ar' => 'الاناره لا تعمل',
                    'label_en' => null,
                    'sort_order' => 188
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::واجهات تبريد::الاناره لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 188
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::واجهات تبريد::الكمبروسر يعمل باستمرار',
                    'parent_key' => 'dev::واجهات تبريد',
                    'label_ar' => 'الكمبروسر يعمل باستمرار',
                    'label_en' => null,
                    'sort_order' => 189
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::واجهات تبريد::الكمبروسر يعمل باستمرار',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 189
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::واجهات تبريد::المنتج لا يعمل',
                    'parent_key' => 'dev::واجهات تبريد',
                    'label_ar' => 'المنتج لا يعمل',
                    'label_en' => null,
                    'sort_order' => 190
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::واجهات تبريد::المنتج لا يعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 190
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::واجهات تبريد::تبريد ضعيف',
                    'parent_key' => 'dev::واجهات تبريد',
                    'label_ar' => 'تبريد ضعيف',
                    'label_en' => null,
                    'sort_order' => 191
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::واجهات تبريد::تبريد ضعيف',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 191
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::واجهات تبريد::كسر في الباب الزجاجي',
                    'parent_key' => 'dev::واجهات تبريد',
                    'label_ar' => 'كسر في الباب الزجاجي',
                    'label_en' => null,
                    'sort_order' => 192
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::واجهات تبريد::كسر في الباب الزجاجي',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 192
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::واجهات تبريد::لوحه التحكم لا تعمل',
                    'parent_key' => 'dev::واجهات تبريد',
                    'label_ar' => 'لوحه التحكم لا تعمل',
                    'label_en' => null,
                    'sort_order' => 193
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'parent_key' => 'prob::واجهات تبريد::لوحه التحكم لا تعمل',
                    'label_ar' => 'تم سحب المنتج الى مركز الصيانة',
                    'label_en' => null,
                    'sort_order' => 193
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'prob::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'parent_key' => 'dev::واجهات تبريد',
                    'label_ar' => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_en' => null,
                    'sort_order' => 194
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'parent_key' => 'prob::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'الجهاز يعمل ولا توجد مشكلة',
                    'label_en' => null,
                    'sort_order' => 194
                ],
                [
                    'type_code' => 'emergency',
                    'key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'parent_key' => 'prob::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                    'label_ar' => 'تم سحب المنتج و الاستبدال',
                    'label_en' => null,
                    'sort_order' => 195
                ]
            ];

            // Create options in a stable order so parents exist before children
            usort($options, function ($a, $b) {
                // parent first
                $pa = $a['parent_key'] ? 1 : 0;
                $pb = $b['parent_key'] ? 1 : 0;
                if ($pa !== $pb) return $pa <=> $pb;
                return $a['sort_order'] <=> $b['sort_order'];
            });

            $optionIdByKey = [];
            // We'll loop until all options are created (handles multi-level parents)
            $remaining = $options;
            $safety = 0;

            while (!empty($remaining) && $safety < 20) {
                $next = [];
                foreach ($remaining as $opt) {
                    $typeId = $typeIdByCode[$opt['type_code']] ?? null;
                    if (!$typeId) {
                        continue;
                    }

                    $parentId = null;
                    if (!empty($opt['parent_key'])) {
                        if (!isset($optionIdByKey[$opt['parent_key']])) {
                            $next[] = $opt; // wait for parent
                            continue;
                        }
                        $parentId = $optionIdByKey[$opt['parent_key']];
                    }

                    $model = AppointmentTypeOption::updateOrCreate(
                        [
                            'appointment_type_id' => $typeId,
                            'parent_id' => $parentId,
                            'label_ar' => $opt['label_ar'],
                        ],
                        [
                            'label_en' => $opt['label_en'],
                            'sort_order' => $opt['sort_order'],
                            'is_active' => true,
                        ]
                    );

                    $optionIdByKey[$opt['key']] = $model->id;
                }

                $remaining = $next;
                $safety++;
            }

            if (!empty($remaining)) {
                // If anything is left, it's usually due to missing parent keys
                // We fail fast so you can catch data issues.
                throw new \RuntimeException('Some appointment_type_options could not be seeded (missing parents). Count=' . count($remaining));
            }

            // 4) Option Fields (required fields per option)
            $links = [
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'field_key' => 'fld_2678cebc_c19',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج اخر',
                    'field_key' => 'fld_2678cebc_c19',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج اخر',
                    'field_key' => 'fld_2678cebc_c19',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'field_key' => 'fld_82302d62_c14',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'field_key' => 'fld_e1c16112_c15',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'field_key' => 'fld_8460f515_c16',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'field_key' => 'fld_335ed8e0_c17',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::periodic::تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'field_key' => 'fld_ddbe24e9_c18',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'field_key' => 'fld_82302d62_c23',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'field_key' => 'fld_e1c16112_c24',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'field_key' => 'fld_8460f515_c25',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'field_key' => 'fld_335ed8e0_c26',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'field_key' => 'fld_ddbe24e9_c27',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'field_key' => 'fld_82302d62_c23',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'field_key' => 'fld_e1c16112_c24',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'field_key' => 'fld_8460f515_c25',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'field_key' => 'fld_335ed8e0_c26',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'field_key' => 'fld_ddbe24e9_c27',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'field_key' => 'fld_f8000877_c28',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك الجهاز',
                    'field_key' => 'fld_572958e4_c29',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب الجهاز',
                    'field_key' => 'fld_82302d62_c23',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب الجهاز',
                    'field_key' => 'fld_e1c16112_c24',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب الجهاز',
                    'field_key' => 'fld_8460f515_c25',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب الجهاز',
                    'field_key' => 'fld_335ed8e0_c26',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب الجهاز',
                    'field_key' => 'fld_ddbe24e9_c27',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تركيب الجهاز',
                    'field_key' => 'fld_f8000877_c28',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'field_key' => 'fld_82302d62_c23',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'field_key' => 'fld_e1c16112_c24',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'field_key' => 'fld_8460f515_c25',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'field_key' => 'fld_335ed8e0_c26',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'field_key' => 'fld_ddbe24e9_c27',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'field_key' => 'fld_f8000877_c28',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و فك و تركيب الجهاز',
                    'field_key' => 'fld_572958e4_c29',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تمديد قارورة او غطاء برادة',
                    'field_key' => 'fld_a82e6360_c30',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تمديد قارورة او غطاء برادة',
                    'field_key' => 'fld_a82e6360_c31',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تمديد قارورة او غطاء برادة',
                    'field_key' => 'fld_a82e6360_c32',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تسليم المنتج للعميل',
                    'field_key' => 'fld_debb040b_c36',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تسليم المنتج للعميل',
                    'field_key' => 'fld_93c3603a_c37',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تسليم المنتج للعميل',
                    'field_key' => 'fld_ea6f24ae_c38',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تسليم المنتج للعميل',
                    'field_key' => 'fld_32b8012d_c39',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تسليم المنتج للعميل',
                    'field_key' => 'fld_1f63a129_c40',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و تسليم المنتج للعميل',
                    'field_key' => 'fld_93c3603a_c41',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_59e829d5_c33',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_0c6804d6_c34',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_af2c1f5f_c35',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_debb040b_c36',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_93c3603a_c37',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_ea6f24ae_c38',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_32b8012d_c39',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_1f63a129_c40',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و سحب المنتج من العميل',
                    'field_key' => 'fld_93c3603a_c41',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و توفير  القطعة',
                    'field_key' => 'fld_debb040b_c36',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'opt::service::تم خدمة العميل و توفير  القطعة',
                    'field_key' => 'fld_93c3603a_c37',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الفاتر يطلع بارد::تم ضبط الفاصل الداخلي للخزان',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الفاتر يطلع بارد::تم ضبط الفاصل الداخلي للخزان',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم توصيل مصدر المياه للمنتج',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم توصيل مصدر المياه للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم ضبط درجة البرودة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادات ذاتيه التعبئة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل أسلاك الحساسات بشكل صحيح',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل أسلاك الحساسات بشكل صحيح',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم توصيل أسلاك الحساسات بشكل صحيح',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تنظيف مصدر الماء',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تنظيف مصدر الماء',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تنظيف مصدر الماء',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس خارج الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس خارج الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس خارج الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة خارج الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة خارج الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة خارج الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول خارج الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول خارج الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول خارج الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة ضمن الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة ضمن الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المضخة ضمن الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول ضمن الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول ضمن الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال المحول ضمن الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تغيير فلاتر',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تغيير فلاتر',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تغيير فلاتر',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تغيير فلاتر',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::الفلتر لا يعمل::تم تغيير فلاتر',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تم تغيير الهاوس',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تم تغيير الهاوس',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تم تغيير الهاوس',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::التهريب من مجلى العميل',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::التهريب من مجلى العميل',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::تهريب::التهريب من مجلى العميل',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::قارورة البرادة تهرب::تم ضبط العوامة',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::قارورة البرادة تهرب::تم ضبط العوامة',
                    'field_key' => 'fld_f8000877_c52',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::قارورة البرادة تهرب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::قارورة البرادة تهرب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_f8000877_c52',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::قارورة البرادة تهرب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::قارورة البرادة تهرب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_f8000877_c52',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::تحلية::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::البارد لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::التماس::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الفاتر يطلع بارد::تم ضبط الفاصل الداخلي للخزان',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الفاتر يطلع بارد::تم ضبط الفاصل الداخلي للخزان',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم توصيل مصدر المياه للمنتج',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم توصيل مصدر المياه للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم استبدال فيوز الفيش',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم تغيير حساس خارج الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال الحساس ضمن الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تصريف مستمر::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم تغيير الهاوس',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم تغيير الهاوس',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم تغيير الهاوس',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تمت معالجة مشكلة التسريب بالكامل',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم استبدال قطع الغيار اللازمة',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::التهريب من مجلى العميل',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::التهريب من مجلى العميل',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::التهريب من مجلى العميل',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم تثبيت الليات وإحكام ربطها',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم تثبيت الليات وإحكام ربطها',
                    'field_key' => 'fld_f8000877_c52',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::صوت مزعج::تم تثبيت الليات وإحكام ربطها',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::توفير قطعة::تم تسليم القطعة للعميل',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تنظيف البوستات',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم استبدال البوستات ضمن الضمان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تغيير فلاتر',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم ضبط نسبة الأملاح',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_82302d62_c47',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_8460f515_c49',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_335ed8e0_c50',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طعم و شوائب::تم تفريغ الماء في الخزان',
                    'field_key' => 'fld_ddbe24e9_c51',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::برادة فلتر داخلي::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::تهريب::تم تسليم القطعة للعميل',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::كسر في الخلاط::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::تدوير الطعام::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::خلاطات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::خلاطات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::خلاطات::توفير قطعة::تم تسليم القطعة للعميل',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::خلاطات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::خلاطات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::خلاطات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::دفايات::الاضاه معطله::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::دفايات::التسخين لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::دفايات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::دفايات::تلف في سلك التوصيل الكهربائي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::دفايات::توفير قطعة::تم تسليم القطعة للعميل',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في أنبوب التسخين::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::دفايات::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::دفايات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::المنتج لا يعمل::تم توصيل الكهرباء (220 فولت) للمنتج',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::توفير قطعة::تم تسليم القطعة للعميل',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::كسر في الواجهه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::صانعه الايسكريم::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::الانتاجيه ضعيفه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صدا في الكويل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طعم و شوائب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::صانعه الثلج::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في سلك توصيل السياره::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::عطل في لوحه التحكم::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::صناديق التبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::فلاتر الشاور والصنابير::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::الحار لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تسخين مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::مشروبات ساخنة::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مضخات::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مضخات::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مضخات::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مضخات::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مضخات::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::مضخات::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الاسلاش لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::الموقت لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::انسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تصريف مستمر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::تهريب::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::توفير قطعة::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::دفع الهواء ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::رائحه سيئه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::صوت مزعج::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الديكور::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الفلتر::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في الواجهه الزجاجيه::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::كسر في خزان الماء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لا يفلتر الهواء::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::نسداد في النزل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::منقيات الهواء::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الاناره لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::الكمبروسر يعمل باستمرار::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::المنتج لا يعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::تبريد ضعيف::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::كسر في الباب الزجاجي::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::لوحه التحكم لا تعمل::تم سحب المنتج الى مركز الصيانة',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::الجهاز يعمل ولا توجد مشكلة',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_e1c16112_c48',
                    'is_required' => true,
                    'sort_order' => 0
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_59e829d5_c55',
                    'is_required' => true,
                    'sort_order' => 1
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_0c6804d6_c56',
                    'is_required' => true,
                    'sort_order' => 2
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_af2c1f5f_c57',
                    'is_required' => true,
                    'sort_order' => 3
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_debb040b_c58',
                    'is_required' => true,
                    'sort_order' => 4
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_ea6f24ae_c59',
                    'is_required' => true,
                    'sort_order' => 5
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_93c3603a_c60',
                    'is_required' => true,
                    'sort_order' => 6
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_32b8012d_c61',
                    'is_required' => true,
                    'sort_order' => 7
                ],
                [
                    'option_key' => 'sol::واجهات تبريد::طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة::تم سحب المنتج و الاستبدال',
                    'field_key' => 'fld_1f63a129_c62',
                    'is_required' => true,
                    'sort_order' => 8
                ]
            ];

            $rows = [];
            foreach ($links as $l) {
                $optionId = $optionIdByKey[$l['option_key']] ?? null;
                $fieldId  = $fieldIdByKey[$l['field_key']] ?? null;

                if (!$optionId || !$fieldId) {
                    continue;
                }

                $rows[] = [
                    'option_id' => $optionId,
                    'field_id' => $fieldId,
                    'is_required' => (bool) $l['is_required'],
                    'sort_order' => (int) $l['sort_order'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Upsert to avoid duplicates
            OptionField::upsert(
                $rows,
                ['option_id', 'field_id'],
                ['is_required', 'sort_order', 'is_active', 'updated_at']
            );
        });
    }
}
