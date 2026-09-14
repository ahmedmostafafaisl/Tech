<?php


namespace App\Repositories\Interfaces;

use App\Models\Appointment;
use Illuminate\Http\Request;

interface AppointmentRepositoryInterface
{
    public function all($perPage, $page, Request $request);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete(Appointment $appointment);
    public function updateStatus(int $id, string $status): Appointment;
    public function getTechnicianAppointments(int $technicianId);

    public function completeAppointment(Appointment $appointment, array $data);

    public function appointmentPaymentStore(int $appointmentId, array $data);
    public function appointmentPaymentUpdate(int $appointmentId, array $data);
    public function getAppointmentPayments($appointmentId);

    public function filterAppointments(Request $request);

    public function searchAppointments(Request $request);

    // new instance methods
    public function newInstanceAppointment(int $appointmentId, array $data);
    // appointment otp methods
    public function verifyOtp($appointment, $otp);

    //    syncAllTechnicianAppointments
    public function syncAllTechnicianAppointments(array $appointments): void;

    public function newSyncAllTechnicianAppointments(array $appointments): void;


    // handle sales line
    public function handleSalesLine(array $data): array;

    // get pending dy appointments
    public function getPendingDyAppointments(int $techId);

    // get last appointment payment
    public function getLastAppointmentPayment(int $appointmentId, string $type);
    // apply discount
    public function applyDiscount(int $appointmentId, float $discountValue, string $discountType);
}
