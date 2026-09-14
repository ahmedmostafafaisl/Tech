<?php

namespace App\Imports;

use App\Models\Service;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ServicesImport implements ToModel, WithHeadingRow, WithEvents
{
    public function model(array $row)
    {
        return new Service([
            'item_number' => $row['rkm_alsnf'] ?? null,
            'name'        => $row['asm_almntg'] ?? null,
            'main_type'   => $row['noaa_almntg'] ?? null,
            'sub_type'    => $row['alnoaa_alfraay_llmntg'] ?? null,
            'price'       => $row['alsaar3'] ?? 0,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function () {
                Service::truncate(); // Deletes all rows from the services table
            },
        ];
    }
}
