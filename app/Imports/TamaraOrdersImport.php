<?php

namespace App\Imports;

use App\Services\Payment\TamaraService;
use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\DirectAppointmentPayment;

class TamaraOrdersImport implements ToModel
{
    public $appointmentsList = [];
    protected $rowCount = 0; // Add a counter

    public function model(array $row)
    {
        $this->rowCount++;

        // Skip first 2 rows
        if ($this->rowCount <= 2) {
            return null;
        }

        $order_id     = $row[0] ?? null;   // id_order
        $reference_id = $row[1] ?? null;   // order_reference_id
        $total_amount = $row[5] ?? null;
        $installments = $row[17] ?? null;

        if (!$reference_id) {
            return null;
        }

        // Fetch payment record
        $payment = DirectAppointmentPayment::where('reference_id', $reference_id)->first();

        if (!$payment) {
            \Log::warning("No payment found for reference: $reference_id");
            return null;
        }

        $appointment = $payment->directAppointment;
        $this->appointmentsList[] = $appointment;

        $tamara = app(TamaraService::class);

        try {
            // STATUS
            $statusResponse = $tamara->getOrderStatus($order_id);

            if (!isset($statusResponse['status'])) {
                \Log::error("Missing Tamara status for: $order_id");
                return null;
            }

            $status = $statusResponse['status'];

            // approved → authorize → capture
            if ($status === 'approved') {
                $auth = $tamara->authorizeOrder($order_id);
                if (($auth['status'] ?? null) === 'authorised') {
                    $tamara->captureOrderNew($reference_id, $order_id, $installments);
                }
            }
            // already authorised → capture
            elseif ($status === 'authorised') {
                $tamara->captureOrderNew($reference_id, $order_id, $installments);
            }
        } catch (\Throwable $e) {
            \Log::error("Tamara Import error: " . $e->getMessage());
        }

        return null;
    }
}
