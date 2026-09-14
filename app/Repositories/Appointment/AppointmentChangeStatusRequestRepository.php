<?php


namespace App\Repositories\Appointment;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use App\Models\AppointmentChangeStatusRequest;
use App\Repositories\Interfaces\AppointmentChangeStatusRequestInterface;

class AppointmentChangeStatusRequestRepository implements AppointmentChangeStatusRequestInterface
{
    public function all()
    {
        return AppointmentChangeStatusRequest::with(['appointment', 'technician', 'customer'])->get();
    }

    public function find($id)
    {
        return AppointmentChangeStatusRequest::with(['appointment', 'technician', 'customer'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return AppointmentChangeStatusRequest::create($data);
    }

    public function update($id, array $data)
    {
        $model = $this->find($id);
        $model->update($data);
        return $model;
    }

    public function delete($id)
    {
        $model = $this->find($id);
        return $model->delete();
    }

    public function getMyRequests()
    {
        return AppointmentChangeStatusRequest::where('technician_id', auth()->id())
            ->latest()
            ->get();
    }

    // sync all technician change status requests
    public function syncAllTechnicianChangeStatusRequests(array $requests)
    {
        DB::beginTransaction();

        try {
            foreach ($requests as $appointmentData) {
                // Get technician and customer by external technician_rec_id
                $appointment = Appointment::where('sales_order_id', $appointmentData['SalesOrderId'])->first();
                $technician = User::where('tech_id', $appointmentData['Requester'])->first();
                $customer =  User::where('technician_rec_id', $appointmentData['CustomerId'])->first();

                $appointment = AppointmentChangeStatusRequest::updateOrCreate(
                    ['rec_id' => $appointmentData['Id']],
                    [
                        'technician_id'     => $technician?->id,
                        'customer_id'       => $customer?->id,
                        'appointment_id'              => $appointment->id ?? null,
                        'sales_order_id' => $appointmentData['SalesOrderId'] ?? null,
                        'status'  => $appointmentData['Status'] ?? null,
                        'type'              => $appointmentData['RequestType'] ?? null,
                        'description'                => $appointmentData['description'] ?? null,
                    ]
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Technician appointments sync failed: " . $e->getMessage());
        }
    }
}
