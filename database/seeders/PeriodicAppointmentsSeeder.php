<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\AppointmentType;
use App\Models\AppointmentTypeOption;
use App\Models\FormField;
use App\Models\OptionField;

class PeriodicAppointmentsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // 1) Ensure appointment type exists
            $type = AppointmentType::updateOrCreate(
                ['code' => 'periodic'],
                [
                    'name_ar' => 'مواعيد الدورية',
                    'name_en' => 'Periodic Appointments',
                    'is_active' => true,
                ]
            );

            // 2) Shared fields (Notes ثابتة + باقي الحقول صور)
            $fields = [
                // Notes ثابتة
                'note_other' => $this->upsertField(
                    'note_other',
                    'ملاحظة أخرى',
                    'Other note',
                    'textarea'
                ),

                // All IMAGE fields
                'salt_home_image' => $this->upsertField(
                    'salt_home_image',
                    'نسبة الأملاح في مياه منزل العميل',
                    'Home water salinity (image)',
                    'image'
                ),
                'salt_after_image' => $this->upsertField(
                    'salt_after_image',
                    'نسبة أملاح الجهاز بعد الخدمة',
                    'Device salinity after service (image)',
                    'image'
                ),
                'carbon_drain_image' => $this->upsertField(
                    'carbon_drain_image',
                    'تفريغ الكربون',
                    'Carbon drain (image)',
                    'image'
                ),
                'sink_cleaning_image' => $this->upsertField(
                    'sink_cleaning_image',
                    'نظافة المجلى و الموقع',
                    'Sink & site cleaning (image)',
                    'image'
                ),
                'connect_drain_image' => $this->upsertField(
                    'connect_drain_image',
                    'ربط لي التصريف',
                    'Drain connection (image)',
                    'image'
                ),

                // يظهر للـ + منتج / + منتج آخر / تسليم باقي الباقة
                'product_at_delivery_image' => $this->upsertField(
                    'product_at_delivery_image',
                    'المنتج عند التسليم',
                    'Product at delivery (image)',
                    'image'
                ),
            ];

            // 3) Periodic options (الخدمة المقدمة للعميل)
            $options = [
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر',
                    'label_en'           => 'Periodic maintenance + filter change (6 months)',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 10,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنة',
                    'label_en'           => 'Periodic maintenance + filter change (1 year)',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 20,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنتين',
                    'label_en'           => 'Periodic maintenance + filter change (2 years)',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 30,
                ],

                [
                    'label_ar'           => 'تم عمل الصيانة الدورية وتغيير الفلاتر 6 أشهر وحل المشكلة الطارئة في الجهاز',
                    'label_en'           => 'Periodic maintenance + filter package (6 months) + emergency issue resolution',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 40,
                    'is_active'          => true,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية وتغيير الفلاتر السنة  وحل المشكلة الطارئة في الجهاز',
                    'label_en'           => 'Periodic maintenance + filter package (1 year) + emergency issue resolution',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 40,
                    'is_active'          => true,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية وتغيير الفلاتر السنتين  وحل المشكلة الطارئة في الجهاز',
                    'label_en'           => 'Periodic maintenance + filter package (2 years) + emergency issue resolution',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 40,
                    'is_active'          => true,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و تسليم باقي الباقة',
                    'label_en'           => 'Periodic maintenance + filter package (6 months) + remaining package delivery',
                    'has_product'        => false,
                    'show_product_image' => true,
                    'sort_order'         => 100,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنة و تسليم باقي الباقة',
                    'label_en'           => 'Periodic maintenance + filter package (1 year) + remaining package delivery',
                    'has_product'        => false,
                    'show_product_image' => true,
                    'sort_order'         => 110,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و تسليم باقي الباقة',
                    'label_en'           => 'Periodic maintenance + filter package (2 years) + remaining package delivery',
                    'has_product'        => false,
                    'show_product_image' => true,
                    'sort_order'         => 120,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر + منتج اخر',
                    'label_en'           => 'Periodic maintenance + filter package (6 months) + another product',
                    'has_product'        => true,
                    'show_product_image' => true,
                    'sort_order'         => 130,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة وتغيير فلتر ممبرين',
                    'label_en'           => 'Periodic maintenance + membrane filter replacement',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 140,
                ],






                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين',
                    'label_en'           => 'Periodic maintenance + filter package (2 years)',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 40,
                    'is_active'          => false,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير باقة الفلاتر 6 أشهر + منتج',
                    'label_en'           => 'Periodic maintenance + filter package (6 months) + product',
                    'has_product'        => true,
                    'show_product_image' => true,
                    'sort_order'         => 50,
                    'is_active'          => false,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير باقة الفلاتر سنتين + منتج',
                    'label_en'           => 'Periodic maintenance + filter package (2 years) + product',
                    'has_product'        => true,
                    'show_product_image' => true,
                    'sort_order'         => 60,
                    'is_active'          => false,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر 6 أشهر و صيانة الجهاز',
                    'label_en'           => 'Periodic maintenance (6 months) + device repair',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 70,
                    'is_active'          => false,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنة و صيانة الجهاز',
                    'label_en'           => 'Periodic maintenance (1 year) + device repair',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 80,
                    'is_active'          => false,
                ],
                [
                    'label_ar'           => 'تم عمل الصيانة الدورية و تغيير الفلاتر السنتين و صيانة الجهاز',
                    'label_en'           => 'Periodic maintenance (2 years) + device repair',
                    'has_product'        => false,
                    'show_product_image' => false,
                    'sort_order'         => 90,
                    'is_active'          => false,
                ],


            ];

            foreach ($options as $opt) {
                // Create/Update option
                $option = AppointmentTypeOption::updateOrCreate(
                    [
                        'appointment_type_id' => $type->id,
                        'parent_id'           => null,
                        'label_ar'            => $opt['label_ar'],
                    ],
                    [
                        'label_en'   => $opt['label_en'],
                        'sort_order' => $opt['sort_order'],
                        'is_active'  => $opt['is_active'] ?? true,
                    ]
                );

                // Replace behavior: disable old links then re-enable needed ones
                OptionField::where('option_id', $option->id)->update(['is_active' => false]);

                // Notes ثابتة مع الكل
                $this->attachField($option->id, $fields['note_other']->id, false, 10);

                // باقي الحقول كلها صور (ثابتة لكل خيارات الدورية)
                $this->attachField($option->id, $fields['salt_home_image']->id, true, 20);
                $this->attachField($option->id, $fields['salt_after_image']->id, true, 30);
                $this->attachField($option->id, $fields['carbon_drain_image']->id, true, 40);
                $this->attachField($option->id, $fields['sink_cleaning_image']->id, true, 50);
                $this->attachField($option->id, $fields['connect_drain_image']->id, true, 60);

                // المنتج عند التسليم: + منتج / + منتج آخر / تسليم باقي الباقة
                if ($opt['show_product_image']) {
                    $this->attachField($option->id, $fields['product_at_delivery_image']->id, true, 70);
                }
            }
        });
    }

    private function upsertField(string $key, string $labelAr, string $labelEn, string $type): FormField
    {
        return FormField::updateOrCreate(
            ['field_key' => $key],
            [
                'label_ar'   => $labelAr,
                'label_en'   => $labelEn,
                'field_type' => $type,
                'is_active'  => true,
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
