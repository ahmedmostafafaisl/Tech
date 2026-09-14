<?php

namespace App\Exports;

use App\Models\Appointment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AppointmentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        $startDate = '2025-10-10';
        $endDate = now()->format('Y-m-d');
        return Appointment::whereIn('status', ['processing', 'complete', 'completed'])
            // ->where('dy365_status', '!=', 'Completed')
            ->get();
    }

    public function map($appointment): array
    {
        $payments = $appointment->payments->map(function ($p) {
            return "Type: {$p->payment_type}, Ref: {$p->payment_reference_id}, Status: {$p->status}, Total: {$p->total_price}";
        })->implode(' | ');

        return [
            $appointment->id,
            $appointment->type,
            $appointment->phone,
            $appointment->discount_value,
            $appointment->sales_order_id,
            $appointment->total_price,
            $appointment->appointment_date,
            $appointment->status,
            $appointment->dy365_status ?? '-',
            $appointment->total_price,
            $appointment->dy_response,
            $payments ?: 'No Payments',
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Type',
            'Phone',
            'Discount',
            'Sales Order ID',
            'Total Price',
            'Appointment Date',
            'Status',
            'DY Status',
            'Total Price',
            'DY Response',
            'Payments',
        ];
    }
}
