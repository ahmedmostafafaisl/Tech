<?php

namespace Database\Seeders;

use App\Models\ChangeRequestReason;
use Illuminate\Database\Seeder;

class ChangeRequestReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            // ===== Reschedule =====
            [
                'reason_rec_id' => 5637144576,
                'reason_type'   => 'Reschedule',
                'reason'        => 'العميل لا يستجيب',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144577,
                'reason_type'   => 'Reschedule',
                'reason'        => 'العميل يرغب في اعادة جدولة الموعد',
                'title_ar'      => 'اضف متى يرغب العميل في الموعد القادم',
                'title_en'      => 'Add when the customer wants the next appointment',
            ],
            [
                'reason_rec_id' => 5637144578,
                'reason_type'   => 'Reschedule',
                'reason'        => 'العميل غير متواجد في الموقع',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144579,
                'reason_type'   => 'Reschedule',
                'reason'        => 'المبلغ غير مكتمل لدى العميل',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144580,
                'reason_type'   => 'Reschedule',
                'reason'        => 'المنتج غير متوفر حاليا',
                'title_ar'      => 'وضح نوع المنتج الغير متوفر',
                'title_en'      => 'Specify the unavailable product type',
            ],
            [
                'reason_rec_id' => 5637144581,
                'reason_type'   => 'Reschedule',
                'reason'        => 'العميل في منطقة عسكرية',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144582,
                'reason_type'   => 'Reschedule',
                'reason'        => 'المسار غير صحيح',
                'title_ar'      => 'وضح المدينة والحي للعميل',
                'title_en'      => 'Specify the city and district for the customer',
            ],
            [
                'reason_rec_id' => 5637144583,
                'reason_type'   => 'Reschedule',
                'reason'        => 'لم يتمكن من زيارة العميل أو تقديم الخدمة',
                'title_ar'      => 'وضح سبب الاعتذار من عدم زيارة العميل أو عدم تمكنه من تقديم الخدمة',
                'title_en'      => 'Explain the reason for not visiting the customer or providing the service',
            ],

            // ===== Cancel =====
            [
                'reason_rec_id' => 5637144584,
                'reason_type'   => 'Cancel',
                'reason'        => 'العميل طلب الغاء الموعد',
                'title_ar'      => 'اضف رقم جوال الفني الاخر وملاحظة تم خدمة العميل من قبل فني اخر',
                'title_en'      => 'Add the other technician phone number and note',
            ],
            [
                'reason_rec_id' => 5637144585,
                'reason_type'   => 'Cancel',
                'reason'        => 'تم خدمة العميل من قبل فني اخر',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144586,
                'reason_type'   => 'Cancel',
                'reason'        => 'العميل لا يرغب بالتعامل مع الشركة',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144587,
                'reason_type'   => 'Cancel',
                'reason'        => 'العميل خارج الشركة و رفض دفع الرسوم',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144588,
                'reason_type'   => 'Cancel',
                'reason'        => 'حجز نوع طلب خطأ',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
            [
                'reason_rec_id' => 5637144589,
                'reason_type'   => 'Cancel',
                'reason'        => 'الموقع غير جاهز',
                'title_ar'      => 'اضف ملاحظاتك هنا',
                'title_en'      => 'Add your notes here',
            ],
        ];

        foreach ($reasons as $reason) {
            ChangeRequestReason::updateOrCreate(
                ['reason_rec_id' => $reason['reason_rec_id']],
                $reason
            );
        }
    }
}
