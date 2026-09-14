<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class FilterAppointmentRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'type'                => 'sometimes|in:installation,periodic,emergency',
            'status'              => 'sometimes|in:pending,on_way,on_site,hold,complete,reschedule,cancel,Completed,Delayed,Scheduled,Processing',
            'billing_status'      => 'sometimes|in:pending,paid,refunded,unpaid',
            'appointment_date'    => 'sometimes|date',
            'appointment_date_from' => 'sometimes|date',
            'appointment_date_to'   => 'sometimes|date|after_or_equal:appointment_date_from',
            'technician_id'       => 'sometimes|exists:users,id',
            'customer_id'         => 'sometimes|exists:users,id',
            'power_socket'        => 'sometimes|boolean',
            'per_page'            => 'sometimes|integer|min:1|max:100',
            'current_page'                => 'sometimes|integer|min:1',
            'q' => 'nullable|string|max:255',
            'sales_order_id' => 'sometimes|integer|exists:sales_orders,id',
        ];
    }
}
