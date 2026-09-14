<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmergencyAppointmentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1) Appointment Type
        $typeId = $this->upsertAndGetId('appointment_types', ['code' => 'emergency'], [
            'code'       => 'emergency',
            'name_ar'    => 'مواعيد الطارئة',
            'name_en'    => 'Emergency appointments',
            'updated_at' => $now,
            'created_at' => $now,
        ]);

        // 2) Fields (note is shared; the rest are images)
        $fields = [
            [
                'field_key'  => 'note_other',
                'label_ar'   => 'ملاحظة أخرى',
                'label_en'   => 'Other note',
                'field_type' => 'textarea'
            ],
            [
                'field_key'  => 'salt_home_image',
                'label_ar'   => 'نسبة الأملاح في مياه منزل العميل',
                'label_en'   => 'Home water salinity (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'salt_after_image',
                'label_ar'   => 'نسبة أملاح الجهاز بعد الخدمة',
                'label_en'   => 'Device salinity after service (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'carbon_drain_image',
                'label_ar'   => 'تفريغ الكربون',
                'label_en'   => 'Carbon drain (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'sink_cleaning_image',
                'label_ar'   => 'نظافة المجلى و الموقع',
                'label_en'   => 'Sink & site cleaning (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'connect_drain_image',
                'label_ar'   => 'ربط لي التصريف',
                'label_en'   => 'Drain connection (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'product_after_install_image',
                'label_ar'   => 'المنتج بعد التركيب',
                'label_en'   => 'Product after install (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'product_after_uninstall_image',
                'label_ar'   => 'المنتج بعد فك الجهاز',
                'label_en'   => 'Product after uninstall (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'extension_image',
                'label_ar'   => 'تمديد',
                'label_en'   => 'Extension (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'electricity_measure_image',
                'label_ar'   => 'قياس الكهرباء',
                'label_en'   => 'Electricity measurement (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'water_temp_measure_image',
                'label_ar'   => 'قياس درجة حرارة الماء',
                'label_en'   => 'Water temperature measurement (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'maintenance_form_image',
                'label_ar'   => 'نموذج طلب صيانة بعد اضافة البيانات و توقيع العميل',
                'label_en'   => 'Maintenance request form signed (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'product_front_image',
                'label_ar'   => 'أمام المنتج',
                'label_en'   => 'Front of product (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'product_back_image',
                'label_ar'   => 'خلف المنتج',
                'label_en'   => 'Back of product (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'product_right_image',
                'label_ar'   => 'يمين المنتج',
                'label_en'   => 'Right of product (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'product_left_image',
                'label_ar'   => 'يسار المنتج',
                'label_en'   => 'Left of product (image)',
                'field_type' => 'image'
            ],
            [
                'field_key'  => 'product_top_image',
                'label_ar'   => 'فوق المنتج',
                'label_en'   => 'Top of product (image)',
                'field_type' => 'image'
            ],
        ];

        $fieldIds = [];
        foreach ($fields as $f) {
            $fieldId = $this->upsertAndGetId('form_fields', ['field_key' => $f['field_key']], [
                'field_key'  => $f['field_key'],
                'label_ar'   => $f['label_ar'],
                'label_en'   => $f['label_en'],
                'field_type' => $f['field_type'],
                'updated_at' => $now,
                'created_at' => $now,
            ]);
            $fieldIds[$f['field_key']] = $fieldId;
        }

        // 3) Emergency form tree: Device -> Problem -> Solution -> Required fields
        $devices = [

            // ---------------------------------------------------------------
            // 1. برادات ذاتيه التعبئة
            // ---------------------------------------------------------------


            [
                'label_ar' => 'برادات ذاتيه التعبئة',
                'label_en' => 'Self-fill coolers',
                'problems' => [
                    [
                        'label_ar'  => 'البارد لا يعمل',
                        'label_en'  => 'Cold not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'البارد لا يعمل',
                                'label_en'        => 'Cold not working',
                                'is_active'       => false,
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم ضبط درجة البرودة',
                                'label_en'        => 'Adjusted cold temperature',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'التماس',
                        'label_en'  => 'Short circuit',
                        'solutions' => [
                            [
                                'label_ar'        => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                                'label_en'        => 'Restarted & verified efficiency',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الحار لا يعمل',
                        'label_en'  => 'Hot not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                                'label_en'        => 'Restarted & verified efficiency',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الفاتر يطلع بارد',
                        'label_en'  => 'Lukewarm comes out cold',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم ضبط الفاصل الداخلي للخزان',
                                'label_en'        => 'Adjusted internal tank separator',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الكمبروسر يعمل باستمرار',
                        'label_en'  => 'Compressor runs continuously',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل مصدر المياه للمنتج',
                                'label_en'        => 'Connected water supply',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                                'label_en'        => 'Restarted & verified efficiency',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تمت معالجة مشكلة التسريب بالكامل',
                                'label_en'        => 'Fixed leak completely',
                                'required_fields' => ['product_back_image'],
                            ],
                            [
                                'label_ar'        => 'تم ضبط درجة البرودة',
                                'label_en'        => 'Adjusted cooling temperature',
                                'required_fields' => ['product_back_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طعم و شوائب',
                        'label_en'  => 'Taste & impurities',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تفريغ الماء في الخزان',
                                'label_en'        => 'Drained tank water',
                                'required_fields' => ['product_front_image', 'product_back_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الديكور',
                        'label_en'  => 'Decoration damage',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    // ✅ NEW
                    [
                        'label_ar'  => 'لا توجد مشكلة',
                        'label_en'  => 'No issue',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 2. تحلية
            // ---------------------------------------------------------------
            [
                'label_ar' => 'تحلية',
                'label_en' => 'Water purifier',
                'problems' => [
                    [
                        'label_ar'  => 'الفلتر لا يعمل',
                        'label_en'  => 'Filter not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => [
                                    'salt_after_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => [
                                    'salt_after_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم توصيل أسلاك الحساسات بشكل صحيح',
                                'label_en'        => 'Connected sensor wires correctly',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم تنظيف مصدر الماء',
                                'label_en'        => 'Cleaned water source',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال الحساس خارج الضمان',
                                'label_en'        => 'Replaced sensor (out of warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال المضخة خارج الضمان',
                                'label_en'        => 'Replaced pump (out of warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال المحول خارج الضمان',
                                'label_en'        => 'Replaced transformer (out of warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال الحساس ضمن الضمان',
                                'label_en'        => 'Replaced sensor (warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال المضخة ضمن الضمان',
                                'label_en'        => 'Replaced pump (warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال المحول ضمن الضمان',
                                'label_en'        => 'Replaced transformer (warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة',
                                'label_en'        => 'Replaced required spare parts',
                                'required_fields' => ['salt_after_image'],
                            ],
                            [
                                'label_ar'        => 'تم تغيير فلاتر',
                                'label_en'        => 'Changed filters',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة برسوم',
                                'label_en'        => 'Replaced required spare parts (with charges)',
                                'is_active'       => false,
                                'required_fields' => ['salt_after_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة ',
                                'label_en'        => 'Replaced required spare parts',
                                'required_fields' => ['salt_after_image'],
                            ],
                            [
                                'label_ar'        => 'تم تغيير فلاتر او بوستات تحت الضمان',
                                'label_en'        => 'Changed filters or boosters (under warranty)',
                                'is_active'       => false,
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم تفريغ الهواء في الجهاز',
                                'label_en'        => 'Purged air from device',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'خطأ فني الليات معكوسة',
                                'label_en'        => 'Technical error: reversed valves',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تصريف مستمر',
                        'label_en'  => 'Continuous drain',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تغيير حساس خارج الضمان',
                                'label_en'        => 'Changed sensor (out of warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال الحساس ضمن الضمان',
                                'label_en'        => 'Replaced sensor (warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تمت معالجة مشكلة التسريب بالكامل',
                                'label_en'        => 'Fixed leak completely',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة',
                                'label_en'        => 'Replaced required spare parts',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تغيير الهاوس',
                                'label_en'        => 'Replaced housing',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تمت معالجة مشكلة التسريب بالكامل',
                                'label_en'        => 'Fixed leak completely',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة',
                                'label_en'        => 'Replaced required spare parts',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'التهريب من مجلى العميل',
                                'label_en'        => 'Leak from customer\'s sink',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طعم و شوائب',
                        'label_en'  => 'Taste & impurities',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تنظيف البوستات',
                                'label_en'        => 'Cleaned boosters',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم استبدال البوستات ضمن الضمان',
                                'label_en'        => 'Replaced boosters (warranty)',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم استبدال ممبرين ضمن الضمان',
                                'label_en'        => 'Replaced membrane (warranty)',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar' => 'تم تغيير فلاتر',
                                'label_en' => 'Changed filters',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم تغيير فلاتر',
                                'label_en'        => 'Changed filters',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم ضبط نسبة الأملاح',
                                'label_en'        => 'Adjusted salinity level',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم تفريغ الماء في الخزان',
                                'label_en'        => 'Drained tank water',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'قارورة البرادة تهرب',
                        'label_en'  => 'Cooler bottle leaking',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم ضبط العوامة',
                                'label_en'        => 'Adjusted float',
                                'required_fields' => ['sink_cleaning_image', 'product_after_install_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة',
                                'label_en'        => 'Replaced required spare parts',
                                'required_fields' => ['sink_cleaning_image', 'product_after_install_image'],
                            ],
                            [
                                'label_ar'        => 'تمت معالجة مشكلة التسريب بالكامل',
                                'label_en'        => 'Fixed leak completely',
                                'required_fields' => ['sink_cleaning_image', 'product_after_install_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'دفع الماء ضعيف',
                        'label_en'  => 'Weak water flow',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم معالجة مشكلة ضعف دفع الماء',
                                'label_en'        => 'Resolved weak water flow',
                                'is_active'       => false,
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم تعبئة الخزان هواء',
                                'label_en'        => 'Filled air tank',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                        ],
                    ],
                    // ✅ NEW
                    [
                        'label_ar'  => 'لا توجد مشكلة',
                        'label_en'  => 'No issue',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_home_image', 'sink_cleaning_image', 'connect_drain_image', 'electricity_measure_image'],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 3. برادة فلتر داخلي
            // ---------------------------------------------------------------
            [
                'label_ar' => 'برادة فلتر داخلي',
                'label_en' => 'Internal-filter cooler',
                'problems' => [
                    [
                        'label_ar'  => 'البارد لا يعمل',
                        'label_en'  => 'Cold not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'التماس',
                        'label_en'  => 'Short circuit',
                        'solutions' => [
                            [
                                'label_ar'        => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                                'label_en'        => 'Restarted & verified efficiency',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الحار لا يعمل',
                        'label_en'  => 'Hot not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                                'label_en'        => 'Restarted & verified efficiency',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الفاتر يطلع بارد',
                        'label_en'  => 'Lukewarm comes out cold',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم ضبط الفاصل الداخلي للخزان',
                                'label_en'        => 'Adjusted internal tank separator',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الكمبروسر يعمل باستمرار',
                        'label_en'  => 'Compressor runs continuously',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل مصدر المياه للمنتج',
                                'label_en'        => 'Connected water supply',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال فيوز الفيش',
                                'label_en'        => 'Replaced plug fuse',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تمت إعادة تشغيل المنتج والتأكد من كفاءتها',
                                'label_en'        => 'Restarted & verified efficiency',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تصريف مستمر',
                        'label_en'  => 'Continuous drain',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تغيير حساس خارج الضمان',
                                'label_en'        => 'Changed sensor (out of warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال الحساس ضمن الضمان',
                                'label_en'        => 'Replaced sensor (warranty)',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تمت معالجة مشكلة التسريب بالكامل',
                                'label_en'        => 'Fixed leak completely',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة',
                                'label_en'        => 'Replaced required spare parts',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تغيير الهاوس',
                                'label_en'        => 'Replaced housing',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تمت معالجة مشكلة التسريب بالكامل',
                                'label_en'        => 'Fixed leak completely',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم استبدال قطع الغيار اللازمة',
                                'label_en'        => 'Replaced required spare parts',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'التهريب من مجلى العميل',
                                'label_en'        => 'Leak from customer\'s sink',
                                'required_fields' => ['salt_after_image', 'sink_cleaning_image', 'connect_drain_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم تثبيت الليات وإحكام ربطها',
                                'label_en'        => 'Secured hoses & tightened',
                                'required_fields' => ['sink_cleaning_image', 'product_after_install_image', 'electricity_measure_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'توفير قطعة',
                        'label_en'  => 'Provide spare part',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تسليم القطعة للعميل',
                                'label_en'        => 'Delivered spare part to customer',
                                'required_fields' => ['product_front_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طعم و شوائب',
                        'label_en'  => 'Taste & impurities',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تنظيف البوستات',
                                'label_en'        => 'Cleaned boosters',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم استبدال البوستات ضمن الضمان',
                                'label_en'        => 'Replaced boosters (warranty)',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم استبدال ممبرين ضمن الضمان',
                                'label_en'        => 'Replaced membrane (warranty)',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم تغيير فلاتر',
                                'label_en'        => 'Changed filters',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم ضبط نسبة الأملاح',
                                'label_en'        => 'Adjusted salinity level',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم تفريغ الماء في الخزان',
                                'label_en'        => 'Drained tank water',
                                'required_fields' => [
                                    'salt_home_image',
                                    'salt_after_image',
                                    'carbon_drain_image',
                                    'sink_cleaning_image',
                                    'connect_drain_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الديكور',
                        'label_en'  => 'Decoration damage',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    // ✅ NEW
                    [
                        'label_ar'  => 'لا توجد مشكلة',
                        'label_en'  => 'No issue',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['electricity_measure_image', 'water_temp_measure_image'],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 4. تدوير الطعام  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'تدوير الطعام',
                'label_en' => 'Food processor',
                'problems' => [
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تسليم القطعة للعميل',
                                'label_en'        => 'Delivered spare part to customer',
                                'required_fields' => ['product_front_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الخلاط',
                        'label_en'  => 'Blender broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['electricity_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 5. خلاطات  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'خلاطات',
                'label_en' => 'Blenders',
                'problems' => [
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'توفير قطعة',
                        'label_en'  => 'Provide spare part',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تسليم القطعة للعميل',
                                'label_en'        => 'Delivered spare part to customer',
                                'required_fields' => ['product_front_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['electricity_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 6. دفايات  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'دفايات',
                'label_en' => 'Heaters',
                'problems' => [
                    [
                        'label_ar'  => 'الاضاه معطله',
                        'label_en'  => 'Light is broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'التسخين لا يعمل',
                        'label_en'  => 'Heating not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تلف في سلك التوصيل الكهربائي',
                        'label_en'  => 'Power cable damaged',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'توفير قطعة',
                        'label_en'  => 'Provide spare part',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تسليم القطعة للعميل',
                                'label_en'        => 'Delivered spare part to customer',
                                'required_fields' => ['product_front_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في أنبوب التسخين',
                        'label_en'  => 'Heating tube broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الديكور',
                        'label_en'  => 'Decoration damage',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['electricity_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 7. صانعه الايسكريم  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'صانعه الايسكريم',
                'label_en' => 'Ice cream maker',
                'problems' => [
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                            [
                                'label_ar'        => 'تم توصيل الكهرباء (220 فولت) للمنتج',
                                'label_en'        => 'Connected power (220V)',
                                'required_fields' => ['electricity_measure_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تمت معالجة مشكلة التسريب بالكامل',
                                'label_en'        => 'Fixed leak completely',
                                'required_fields' => [],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'توفير قطعة',
                        'label_en'  => 'Provide spare part',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم تسليم القطعة للعميل',
                                'label_en'        => 'Delivered spare part to customer',
                                'required_fields' => ['product_front_image'],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الواجهه',
                        'label_en'  => 'Front panel broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 8. صانعه الثلج  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'صانعه الثلج',
                'label_en' => 'Ice maker',
                'problems' => [
                    [
                        'label_ar'  => 'الانتاجيه ضعيفه',
                        'label_en'  => 'Low productivity',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صدا في الكويل',
                        'label_en'  => 'Coil rust',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طعم و شوائب',
                        'label_en'  => 'Taste & impurities',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 9. صناديق التبريد  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'صناديق التبريد',
                'label_en' => 'Cooler boxes',
                'problems' => [
                    [
                        'label_ar'  => 'الكمبروسر يعمل باستمرار',
                        'label_en'  => 'Compressor runs continuously',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تبريد ضعيف',
                        'label_en'  => 'Weak cooling',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تسخين مستمر',
                        'label_en'  => 'Continuous heating',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'عطل في سلك توصيل السياره',
                        'label_en'  => 'Car power cable issue',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'عطل في لوحه التحكم',
                        'label_en'  => 'Control board issue',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الديكور',
                        'label_en'  => 'Decoration damage',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 10. فلاتر الشاور والصنابير  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'فلاتر الشاور والصنابير',
                'label_en' => 'Shower & tap filters',
                'problems' => [
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'توفير قطعة',
                        'label_en'  => 'Provide spare part',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 11. مشروبات ساخنة  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'مشروبات ساخنة',
                'label_en' => 'Hot beverage machines',
                'problems' => [
                    [
                        'label_ar'  => 'الحار لا يعمل',
                        'label_en'  => 'Hot not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تسخين مستمر',
                        'label_en'  => 'Continuous heating',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'توفير قطعة',
                        'label_en'  => 'Provide spare part',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في خزان الماء',
                        'label_en'  => 'Water tank broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 12. مضخات  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'مضخات',
                'label_en' => 'Pumps',
                'problems' => [
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'يعمل باستمرار',
                        'label_en'  => 'Runs continuously',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 13. منقيات الهواء  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'منقيات الهواء',
                'label_en' => 'Air purifiers',
                'problems' => [
                    [
                        'label_ar'  => 'الاسلاش لا يعمل',
                        'label_en'  => 'Splash not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الموقت لا يعمل',
                        'label_en'  => 'Timer not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'انسداد في النزل',
                        'label_en'  => 'Nozzle clogged',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تصريف مستمر',
                        'label_en'  => 'Continuous drain',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تهريب',
                        'label_en'  => 'Leak',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'توفير قطعة',
                        'label_en'  => 'Provide spare part',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'دفع الهواء ضعيف',
                        'label_en'  => 'Weak airflow',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'رائحه سيئه',
                        'label_en'  => 'Bad smell',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'صوت مزعج',
                        'label_en'  => 'Noise',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الديكور',
                        'label_en'  => 'Decoration damage',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الفلتر',
                        'label_en'  => 'Filter broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الواجهه الزجاجيه',
                        'label_en'  => 'Glass front panel broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في خزان الماء',
                        'label_en'  => 'Water tank broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لا يفلتر الهواء',
                        'label_en'  => 'Does not filter air',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'نسداد في النزل',
                        'label_en'  => 'Nozzle blockage',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'يعمل باستمرار',
                        'label_en'  => 'Runs continuously',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // 14. واجهات تبريد  (unchanged)
            // ---------------------------------------------------------------
            [
                'label_ar' => 'واجهات تبريد',
                'label_en' => 'Refrigerated displays',
                'problems' => [
                    [
                        'label_ar'  => 'الاناره لا تعمل',
                        'label_en'  => 'Light not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'الكمبروسر يعمل باستمرار',
                        'label_en'  => 'Compressor runs continuously',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'المنتج لا يعمل',
                        'label_en'  => 'Product not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'تبريد ضعيف',
                        'label_en'  => 'Weak cooling',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'كسر في الباب الزجاجي',
                        'label_en'  => 'Glass door broken',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'لوحه التحكم لا تعمل',
                        'label_en'  => 'Control board not working',
                        'solutions' => [
                            [
                                'label_ar'        => 'تم سحب المنتج الى مركز الصيانة',
                                'label_en'        => 'Taken to service center',
                                'required_fields' => [
                                    'electricity_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                    [
                        'label_ar'  => 'طلب رفع تقرير عن المنتج و استبدال عند وجود مشكلة',
                        'label_en'  => 'Request report & replacement if needed',
                        'solutions' => [
                            [
                                'label_ar'        => 'الجهاز يعمل ولا توجد مشكلة',
                                'label_en'        => 'Device works (no issue)',
                                'required_fields' => ['salt_after_image', 'electricity_measure_image', 'water_temp_measure_image'],
                            ],
                            [
                                'label_ar'        => 'تم سحب المنتج و الاستبدال',
                                'label_en'        => 'Picked up & replaced product',
                                'required_fields' => [
                                    'salt_after_image',
                                    'electricity_measure_image',
                                    'water_temp_measure_image',
                                    'maintenance_form_image',
                                    'product_front_image',
                                    'product_back_image',
                                    'product_right_image',
                                    'product_left_image',
                                    'product_top_image',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Clear old tree for this type (keep fields)
        DB::table('appointment_type_options')->where('appointment_type_id', $typeId)->delete();

        $deviceOrder = 10;
        foreach ($devices as $device) {
            $deviceId = $this->insertOption($typeId, null, $device['label_ar'], $device['label_en'], $deviceOrder);
            $deviceOrder += 10;

            $problemOrder = 10;
            foreach ($device['problems'] as $problem) {
                $problemId = $this->insertOption($typeId, $deviceId, $problem['label_ar'], $problem['label_en'], $problemOrder);
                $problemOrder += 10;

                $solutionOrder = 10;
                foreach ($problem['solutions'] as $solution) {
                    $solutionId = $this->insertOption($typeId, $problemId, $solution['label_ar'], $solution['label_en'], $solutionOrder, $solution['is_active'] ?? true);
                    $solutionOrder += 10;

                    // Sync fields for this solution
                    DB::table('option_fields')->where('option_id', $solutionId)->delete();

                    $sort = 10;

                    // note_other is always attached (NOT required)
                    $this->attachField($solutionId, $fieldIds['note_other'], false, $sort);
                    $sort += 10;

                    foreach ($solution['required_fields'] as $fieldKey) {
                        if (!isset($fieldIds[$fieldKey])) {
                            continue;
                        }
                        $this->attachField($solutionId, $fieldIds[$fieldKey], true, $sort);
                        $sort += 10;
                    }
                }
            }
        }
    }

    private function upsertAndGetId(string $table, array $unique, array $data): int
    {
        DB::table($table)->updateOrInsert($unique, $data);
        return (int) DB::table($table)->where($unique)->value('id');
    }

    private function insertOption(int $typeId, ?int $parentId, string $ar, string $en, int $sortOrder, bool $isActive = true): int
    {
        return $this->upsertAndGetId('appointment_type_options', [
            'appointment_type_id' => $typeId,
            'parent_id'           => $parentId,
            'label_ar'            => $ar,
        ], [
            'appointment_type_id' => $typeId,
            'parent_id'           => $parentId,
            'label_ar'            => $ar,
            'label_en'            => $en,
            'sort_order'          => $sortOrder,
            'is_active'           => $isActive,
            'updated_at'          => now(),
            'created_at'          => now(),
        ]);
    }

    private function attachField(int $optionId, int $fieldId, bool $isRequired, int $sortOrder): void
    {
        DB::table('option_fields')->insert([
            'option_id'   => $optionId,
            'field_id'    => $fieldId,
            'is_required' => $isRequired ? 1 : 0,
            'sort_order'  => $sortOrder,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
