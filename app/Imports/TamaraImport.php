<?php

namespace App\Imports;

use App\Models\Service;
use App\Models\DirectAppointment;
use App\Services\Payment\TamaraService;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\DirectAppointmentPayment;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TamaraImport implements ToModel, WithHeadingRow, WithEvents, WithStartRow
{
    public function startRow(): int
    {
        return 3;
    }
    public $appointmentsList = [];

    public function model(array $row)
    {

        $order_id = $row["id_order"];
        $reference_id = $row["order_reference_id"];
        $total_amount = $row["total_amount"];
        $installments = $row["instalments"];


        if (!$reference_id) {
            return null;
        }

        // Fetch appointments
        $payments = DirectAppointmentPayment::where('reference_id', $reference_id)->first();

        // Save them in a class property
        if ($payments) {
            $this->appointmentsList[] = $payments->directAppointment;
            try {
                // Call Tamara API for this order
                $statusResponse = app(TamaraService::class)->getOrderStatus($order_id);
                if (isset($statusResponse['status'])) {
                    if ($statusResponse['status'] === 'approved') {
                        $authResponse = app(TamaraService::class)->authorizeOrder($order_id);
                        // $captureResponse = app(TamaraService::class)->captureOrderNew($referenceId, $order_id);

                        if (isset($authResponse['status']) && $authResponse['status'] === 'authorised') {
                            $captureResponse = app(TamaraService::class)->captureOrderNew($reference_id, $order_id);
                        }
                    } elseif ($statusResponse['status'] === 'authorised') {
                        $captureResponse = app(TamaraService::class)->captureOrderNew($reference_id, $order_id);
                    }

                    // Mark success regardless of status flow
                    dd($captureResponse);
                }
            } catch (\Throwable $e) {
                \Log::error('getAppointmentBySalesOrder failed: ' . $e->getMessage());
            }
        }

        return null; // No model created
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
