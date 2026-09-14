<?php

declare(strict_types=1);

namespace App\Exports;

use App\Repositories\Dashboard\DashboardRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Month-only export used exclusively by the PowerBI endpoint.
 *
 * Uses WithColumnWidths with FIXED values (not ShouldAutoSize) — fixed
 * widths cost nothing extra to apply, unlike ShouldAutoSize which
 * measures every cell's rendered font width and scales badly with row
 * count. This keeps the sheet readable without reintroducing the
 * performance problem that was originally removed.
 */
class PreAppointmentMessagesPowerBiExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths
{
    public function __construct(
        private readonly Request $request,
    ) {}

    public function query()
    {
        return app(DashboardRepository::class)
            ->buildFilteredPreMessagesQueryMonth($this->request);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Phone',
            'Sales Order',
            'Book ID',
            'Customer Name',
            'Appointment Type',
            'Appointment Date',
            'Appointment Items',
            'Sent',
            'Status',
            'Sent At',
            'Response At',
        ];
    }

    public function map($message): array
    {
        return [
            $message->id,
            $message->phone,
            $message->sales_order,
            $message->book_id,
            $message->name,
            $message->type,
            $message->date,
            $message->items,
            $message->is_sent ? 'Yes' : 'No',
            $message->customer_response,
            // $message->created_at is a plain string here (DB::table(),
            // not Eloquent) — parse it explicitly rather than relying on
            // optional()->format(), which silently returns null for
            // non-object values like raw strings.
            $message->created_at ? Carbon::parse($message->created_at)->format('Y-m-d H:i:s') : '',
            $message->updated_at ? Carbon::parse($message->updated_at)->format('Y-m-d H:i:s') : '',
        ];
    }

    /**
     * Column letters match the headings() order above. Fixed, reasonable
     * widths — adjust any of these to taste.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,  // ID
            'B' => 15, // Phone
            'C' => 14, // Sales Order
            'D' => 14, // Book ID
            'E' => 22, // Customer Name
            'F' => 16, // Appointment Type
            'G' => 14, // Appointment Date
            'H' => 30, // Appointment Items
            'I' => 8,  // Sent
            'J' => 12, // Status
            'K' => 18, // Sent At
            'L' => 18, // Response At
        ];
    }
}
