<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\AppointmentType;
use App\Models\AppointmentTypeOption;
use App\Models\FormField;
use App\Models\OptionField;

class ServiceAppointmentsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1) Ensure appointment type exists
            $type = AppointmentType::updateOrCreate(
                ['code' => 'service'],
                [
                    'name_ar' => 'مواعيد الخدمات',
                    'name_en' => 'Service Appointments',
                    'is_active' => true,
                ]
            );

            // 2) Fields (note textarea + all other fields are images)
            $fields = [
                // Notes ثابتة مع الكل
                'note_other' => $this->upsertField('note_other', 'ملاحظة أخرى', 'Other note', 'textarea'),

                // Base images
                'salt_home_image'     => $this->upsertField('salt_home_image', 'نسبة الأملاح في مياه منزل العميل', 'Home water salinity (image)', 'image'),
                'salt_after_image'    => $this->upsertField('salt_after_image', 'نسبة أملاح الجهاز بعد الخدمة', 'Device salinity after service (image)', 'image'),
                'carbon_drain_image'  => $this->upsertField('carbon_drain_image', 'تفريغ الكربون', 'Carbon drain (image)', 'image'),
                'sink_cleaning_image' => $this->upsertField('sink_cleaning_image', 'نظافة المجلى و الموقع', 'Sink & site cleaning (image)', 'image'),
                'connect_drain_image' => $this->upsertField('connect_drain_image', 'ربط لي التصريف', 'Drain connection (image)', 'image'),

                // Product state images
                'product_after_install_image'   => $this->upsertField('product_after_install_image', 'المنتج بعد التركيب', 'Product after installation (image)', 'image'),
                'product_after_uninstall_image' => $this->upsertField('product_after_uninstall_image', 'المنتج بعد فك الجهاز', 'Product after uninstall (image)', 'image'),

                // Extension (ONE image)
                'extension_image' => $this->upsertField('extension_image', 'تمديد', 'Extension (image)', 'image'),

                // Measurements
                'electricity_measure_image' => $this->upsertField('electricity_measure_image', 'قياس الكهرباء', 'Electricity measurement (image)', 'image'),
                'water_temp_measure_image'  => $this->upsertField('water_temp_measure_image', 'قياس درجة حرارة الماء', 'Water temperature measurement (image)', 'image'),

                // Signed maintenance request form
                'maintenance_request_form_image' => $this->upsertField(
                    'maintenance_request_form_image',
                    'نموذج طلب صيانة بعد اضافة البيانات و توقيع العميل',
                    'Maintenance request form + signature (image)',
                    'image'
                ),

                // Product angles
                'product_front_image' => $this->upsertField('product_front_image', 'أمام المنتج', 'Front of product (image)', 'image'),
                'product_back_image'  => $this->upsertField('product_back_image', 'خلف المنتج', 'Back of product (image)', 'image'),
                'product_left_image'  => $this->upsertField('product_left_image', 'يسار المنتج', 'Left of product (image)', 'image'),
                'product_top_image'   => $this->upsertField('product_top_image', 'فوق المنتج', 'Top of product (image)', 'image'),
                'product_right_image' => $this->upsertField('product_right_image', 'يمين المنتج', 'Right of product (image)', 'image'),
            ];

            /**
             * 3) Options with exact fields & exact sort_order
             * field_map format: [ ['key' => '...', 'sort' => 20], ... ]
             */
            $options = [
                [
                    'label_ar' => 'تم خدمة العميل و تغيير الفلاتر من الباقة عند العميل',
                    'label_en' => 'Served customer + changed filters (customer package)',
                    'sort_order' => 10,
                    'field_map' => [
                        ['key' => 'salt_home_image', 'sort' => 20],
                        ['key' => 'salt_after_image', 'sort' => 30],
                        ['key' => 'carbon_drain_image', 'sort' => 40],
                        ['key' => 'sink_cleaning_image', 'sort' => 50],
                        ['key' => 'connect_drain_image', 'sort' => 60],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و تركيب جهاز العميل و تغيير الفلاتر',
                    'label_en' => 'Served customer + installed device + changed filters',
                    'sort_order' => 20,
                    'field_map' => [
                        ['key' => 'salt_home_image', 'sort' => 20],
                        ['key' => 'salt_after_image', 'sort' => 30],
                        ['key' => 'carbon_drain_image', 'sort' => 40],
                        ['key' => 'sink_cleaning_image', 'sort' => 50],
                        ['key' => 'connect_drain_image', 'sort' => 60],
                        ['key' => 'product_after_install_image', 'sort' => 70],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و فك الجهاز',
                    'label_en' => 'Served customer + uninstalled device',
                    'sort_order' => 30,
                    'field_map' => [
                        // ✅ حسب كلامك: فقط صورة فك الجهاز (والملاحظات ثابتة)
                        ['key' => 'product_after_uninstall_image', 'sort' => 70],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و تركيب الجهاز',
                    'label_en' => 'Served customer + installed device',
                    'sort_order' => 40,
                    'field_map' => [
                        ['key' => 'salt_home_image', 'sort' => 20],
                        ['key' => 'salt_after_image', 'sort' => 30],
                        ['key' => 'carbon_drain_image', 'sort' => 40],
                        ['key' => 'sink_cleaning_image', 'sort' => 50],
                        ['key' => 'connect_drain_image', 'sort' => 60],
                        ['key' => 'product_after_install_image', 'sort' => 70],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و فك و تركيب الجهاز',
                    'label_en' => 'Served customer + uninstall & reinstall',
                    'sort_order' => 50,
                    'field_map' => [
                        ['key' => 'salt_home_image', 'sort' => 20],
                        ['key' => 'salt_after_image', 'sort' => 30],
                        ['key' => 'carbon_drain_image', 'sort' => 40],
                        ['key' => 'sink_cleaning_image', 'sort' => 50],
                        ['key' => 'connect_drain_image', 'sort' => 60],
                        ['key' => 'product_after_install_image', 'sort' => 70],
                        ['key' => 'product_after_uninstall_image', 'sort' => 80],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و تمديد قارورة او غطاء برادة',
                    'label_en' => 'Served customer + extension (bottle/cooler cap)',
                    'sort_order' => 60,
                    'field_map' => [
                        // ✅ تمديد صورة واحدة فقط
                        ['key' => 'extension_image', 'sort' => 20],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و تسليم المنتج للعميل',
                    'label_en' => 'Served customer + delivered product',
                    'sort_order' => 70,
                    'field_map' => [
                        // ✅ حسب ملاحظتك: صور الزوايا فقط (بدون قياسات/نموذج)
                        ['key' => 'product_front_image', 'sort' => 50],
                        ['key' => 'product_back_image',  'sort' => 60],
                        ['key' => 'product_left_image',  'sort' => 70],
                        ['key' => 'product_top_image',   'sort' => 80],
                        ['key' => 'product_right_image', 'sort' => 90],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و سحب المنتج من العميل',
                    'label_en' => 'Served customer + collected product',
                    'sort_order' => 80,
                    'field_map' => [
                        // (لسه زي الشيت غالبًا: قياسات + نموذج + صور الزوايا)
                        ['key' => 'electricity_measure_image', 'sort' => 20],
                        ['key' => 'water_temp_measure_image',  'sort' => 30],
                        ['key' => 'maintenance_request_form_image', 'sort' => 40],

                        ['key' => 'product_front_image', 'sort' => 50],
                        ['key' => 'product_back_image',  'sort' => 60],
                        ['key' => 'product_left_image',  'sort' => 70],
                        ['key' => 'product_top_image',   'sort' => 80],
                        ['key' => 'product_right_image', 'sort' => 90],
                    ],
                ],
                [
                    'label_ar' => 'تم خدمة العميل و توفير القطعة',
                    'label_en' => 'Served customer + provided spare part',
                    'sort_order' => 90,
                    'field_map' => [
                        ['key' => 'product_front_image', 'sort' => 50],
                    ],
                ],
            ];

            foreach ($options as $opt) {
                $option = AppointmentTypeOption::updateOrCreate(
                    [
                        'appointment_type_id' => $type->id,
                        'parent_id' => null,
                        'label_ar' => $opt['label_ar'],
                    ],
                    [
                        'label_en' => $opt['label_en'],
                        'sort_order' => $opt['sort_order'],
                        'is_active' => true,
                    ]
                );

                // Replace behavior: disable old links then enable needed ones
                OptionField::where('option_id', $option->id)->update(['is_active' => false]);

                // ✅ Notes ثابتة مع الكل
                $this->attachField($option->id, $fields['note_other']->id, true, 10);

                // Attach ONLY mapped fields (required)
                foreach (($opt['field_map'] ?? []) as $row) {
                    $key = $row['key'] ?? null;
                    $sort = (int)($row['sort'] ?? 0);

                    if (!$key || !isset($fields[$key])) {
                        continue;
                    }

                    $this->attachField(
                        $option->id,
                        $fields[$key]->id,
                        true,
                        $sort
                    );
                }
            }
        });
    }

    private function upsertField(string $key, string $labelAr, string $labelEn, string $type): FormField
    {
        return FormField::updateOrCreate(
            ['field_key' => $key],
            [
                'label_ar' => $labelAr,
                'label_en' => $labelEn,
                'field_type' => $type,
                'is_active' => true,
            ]
        );
    }

    private function attachField(int $optionId, int $fieldId, bool $required, int $sortOrder): void
    {
        OptionField::updateOrCreate(
            ['option_id' => $optionId, 'field_id' => $fieldId],
            [
                'is_required' => $required,
                'sort_order'  => $sortOrder,
                'is_active'   => true,
            ]
        );
    }
}
