<?php

namespace App\Exports;

use App\Models\DirectAppointment;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DirectTodayAppointmentsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }
    public function collection()
    {

        return DirectAppointment::with('payments')
            ->where('status', 'paid')
            ->whereDate('created_at', $this->date)   // filter by date
            ->get();
    }

    public function map($appointment): array
    {
        $payments = $appointment->payments->map(function ($p) {
            return "Type: {$p->payment_type}, Ref: {$p->reference_id}, Status: {$p->status}, Total: {$p->price}";
        })->implode(' | ');

        return [
            $appointment->id,
            $appointment->book_id,
            $appointment->sales_order_id,
            $appointment->discount ?? 0,
            $appointment->total_price,
            $appointment->status,
            $appointment->dy_response ?? '-',
            $payments ?: 'No Payments',
            $appointment->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Book ID',
            'Sales Order ID',
            'Discount',
            'Total Price',
            'Status',
            'DY Response',
            'Payments',
            'Created At',
        ];
    }
}
