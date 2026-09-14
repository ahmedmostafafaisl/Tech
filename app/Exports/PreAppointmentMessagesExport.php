<?php

declare(strict_types=1);

namespace App\Exports;

use App\Repositories\Dashboard\DashboardRepository;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PreAppointmentMessagesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithColumnWidths, WithEvents
{
    public function __construct(
        private readonly Request $request,
    ) {}

    /**
     * FromQuery (not FromCollection) so maatwebsite/excel chunks the
     * result set internally rather than loading every row into memory
     * at once — safe for large exports.
     */
    public function query()
    {
        return app(DashboardRepository::class)
            ->buildFilteredPreMessagesQuery($this->request);
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
            optional($message->created_at)->format('Y-m-d H:i:s'),
            optional($message->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Column letters match the headings() order above:
     * A=ID B=Phone C=Sales Order D=Book ID E=Customer Name
     * F=Appointment Type G=Appointment Date H=Appointment Items
     * I=Sent J=Status K=Sent At L=Response At
     *
     * Only E and H get a fixed width here — everything else still
     * auto-sizes via ShouldAutoSize, since WithColumnWidths values take
     * precedence only for the columns explicitly listed.
     */
    public function columnWidths(): array
    {
        return [
            'E' => 20, // Customer Name
            'H' => 30, // Appointment Items
        ];
    }

    /**
     * Fixed width alone just cuts long text off visually — wrap it so the
     * row grows taller instead, keeping the full value visible.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->getStyle("E1:E{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);

                $sheet->getStyle("H1:H{$lastRow}")
                    ->getAlignment()
                    ->setWrapText(true)
                    ->setVertical(Alignment::VERTICAL_TOP);
            },
        ];
    }
}
