<?php

namespace App\Http\Requests\WhatsApp;

use Illuminate\Foundation\Http\FormRequest;

class SendWhatsAppMessageRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'phone'    => 'required|string|regex:/^[0-9]{10,15}$/',
            'name'     => 'required|string|max:255',
            'type'     => 'required|string|max:255',
            'date'     => 'required|string|max:50',
            'items'    => 'nullable|string|max:1000',
            'notify_type' => 'required|in:create,update,confirmation,replace',
            'bookId' => 'nullable|string',
            'worker_id' => 'nullable|integer|required_if:notify_type,replace',
            'to_worker' => 'nullable|integer|required_if:notify_type,replace',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.regex'    => 'رقم الهاتف يجب أن يكون بالصيغة الدولية (مثال: 2011XXXXXXX)',
            'name.required'  => 'الاسم مطلوب',
            'type.required'  => 'النوع مطلوب',
            'date.required'  => 'التاريخ مطلوب',
            'items.required' => 'العناصر مطلوبة',
            'notify_type.required' => 'نوع الإشعار مطلوب',
            'notify_type.in'       => 'نوع الإشعار يجب أن يكون إما create أو update',
            'worker_id.required_if' => 'رقم الفني الحالي مطلوب عند استبدال الموعد',
            'to_worker.required_if' => 'رقم الفني الجديد مطلوب عند استبدال الموعد',
        ];
    }
}
