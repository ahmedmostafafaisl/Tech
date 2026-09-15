<?php

namespace App\Services\Payment;

use App\Http\Controllers\Api\NewDirectIntegrationController;
use App\Models\DirectAppointment;

/**
 * Fetches fresh appointment data from DY365 (via refSingleAppointmentByBookId),
 * falling back to whatever's stored locally if the DY365 call fails or
 * returns nothing usable.
 *
 * Extracted from PaymentCompletionDispatcher::getAppointmentDataFromDynamics()
 * so payment reconciliation services (Tabby now, Tamara/ClickPay in
 * upcoming steps) that also need live PaidAmount/used_balance don't each
 * duplicate this same fetch-with-fallback logic. PaymentCompletionDispatcher
 * itself is left using its own existing method for this step — not
 * touched here, since it already works and isn't part of this step's scope.
 */
class DynamicsAppointmentDataFetcher
{
    public function fetch(DirectAppointment $appointment): array
    {
        try {
            $controller = app(NewDirectIntegrationController::class);
            $singleAppointment = $controller->refSingleAppointmentByBookId($appointment->book_id);

            if ($singleAppointment instanceof \Illuminate\Http\JsonResponse) {
                $singleAppointment = $singleAppointment->getData(true);
            }

            if (empty($singleAppointment) || !is_array($singleAppointment)) {
                return $this->dbFallback($appointment);
            }

            return $singleAppointment + ['source' => 'dynamics'];
        } catch (\Throwable $e) {
            return $this->dbFallback($appointment);
        }
    }

    private function dbFallback(DirectAppointment $appointment): array
    {
        return [
            'book_id'         => $appointment->book_id,
            'sales_order_id'  => $appointment->sales_order_id,
            'required_amount' => (float) ($appointment->required_amount ?? 0),
            'sales_lines'     => [],
            'source'          => 'db_fallback',
        ];
    }
}
