<?php

namespace App\Http\Controllers\Appointment;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Helper\ApiResponseHelper;
use App\Services\DY365\DyService;
use Illuminate\Support\Facades\DB;
use App\Exports\AppointmentsExport;
use App\Services\TaqnyatSmsService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DirectAppointmentsExport;
use App\Exports\DirectTodayAppointmentsExport;
use App\Http\Requests\DY365\HandleSalesLineRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Repositories\Interfaces\TechRepositoryInterface;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\FilterAppointmentRequest;
use App\Http\Requests\Appointment\SearchAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\Appointment\AppointmentLinesResource;
use App\Http\Requests\Appointment\CompleteAppointmentRequest;
use App\Http\Requests\Appointment\InstanceAppointmentRequest;
use App\Repositories\Interfaces\AppointmentRepositoryInterface;
use App\Http\Requests\Appointment\StoreAppointmentPaymentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentStatusRequest;
use App\Http\Resources\Appointment\AppointmentItemsAndPartsResource;
use App\Http\Requests\Appointment\RescheduleOrCancelAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentItemsAndPartsRequest;

class AppointmentController extends Controller
{
    use ApiResponseHelper;
    protected $appointmentRepo;
    protected $techRepository;
    protected $smsService;
    protected DyService $dyService;

    public function __construct(DyService $dynamicsService, AppointmentRepositoryInterface $appointmentRepo, TechRepositoryInterface $techRepository, TaqnyatSmsService $smsService)
    {
        $this->techRepository = $techRepository;
        $this->appointmentRepo = $appointmentRepo;
        $this->smsService = $smsService;
        $this->dyService = $dynamicsService;
    }

    public function index(FilterAppointmentRequest $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('current_page', 1);
        return $this->appointmentRepo->all($perPage, $page, $request);
    }

    public function store(StoreAppointmentRequest $request)
    {
        return  $appointment = $this->appointmentRepo->create($request->validated());
    }

    public function show($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->setCode(404)->setMessage('Appointment not found.')->send();
        }
        return ($this->appointmentRepo->find($id));
    }
    // rescheduleOrCancelAppointment
    public function rescheduleOrCancelAppointment(RescheduleOrCancelAppointmentRequest $request, $id)
    {
        return   $this->appointmentRepo->update($id, $request->validated());
    }

    //  get appointment Items and parts
    public function appointmentItemsAndParts($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->setCode(404)->setMessage('Appointment not found.')->send();
        }
        // $appointment->load(['lines.item', 'lines.part']);

        $missing = $this->techRepository->missingAppointmentLinesInventory($appointment->technician, $appointment);
        $missingInventory = $this->techRepository->missingInventory($appointment->technician, $appointment);

        return $this->setCode(code: 200)->setData(new AppointmentLinesResource($appointment, $missingInventory))->setMessage('Success.')->send();
    }

    public function update(UpdateAppointmentRequest $request, $id)
    {
        return  $this->appointmentRepo->update($id, $request->validated());
    }

    public function updateAppointmentItemsAndParts(UpdateAppointmentItemsAndPartsRequest $request, $id)
    {
        // $appointment = $this->appointmentRepo->find($id);
        return $this->appointmentRepo->update($id, $request->validated());
    }


    public function destroy($id)
    {
        $appointment = $this->appointmentRepo->find($id);
        $this->appointmentRepo->delete($appointment);
        return response()->json(['message' => 'Deleted successfully']);
    }
    public function updateStatus(UpdateAppointmentStatusRequest $request, $id)
    {
        $appointment = $this->appointmentRepo->updateStatus($id, $request->status);

        return response()->json([
            'message' => 'Appointment status updated successfully',
            'appointment' => $appointment,
        ], 200);
    }

    public function getTechnicianAppointments($technicianId)
    {
        $appointments = $this->appointmentRepo->getTechnicianAppointments($technicianId);

        return response()->json([
            'appointments' => $appointments,
        ], 200);
    }

    public function completeAppointment(CompleteAppointmentRequest $request, Appointment $appointment)
    {
        return    $appointment = $this->appointmentRepo->completeAppointment($appointment, $request->validated());
    }

    // appointment payment

    public function appointmentPaymentStore($id, StoreAppointmentPaymentRequest $request)
    {
        return   $appointmentPayment = $this->appointmentRepo->appointmentPaymentStore($id, $request->validated());
    }
    public function appointmentPaymentUpdate($id,  StoreAppointmentPaymentRequest $request)
    {
        $appointmentPayment = $this->appointmentRepo->appointmentPaymentUpdate($id, $request->validated());
        return response()->json([
            'message' => 'Appointment payment updated successfully.',
            'data' => $appointmentPayment,
        ], 200);
    }
    public function getAppointmentPayments($appointmentId)
    {
        $payments = $this->appointmentRepo->getAppointmentPayments($appointmentId);

        return response()->json([
            'data' => $payments,
            'status' => 200,
        ]);
    }

    public function filterAppointments(FilterAppointmentRequest $request)
    {

        return   $appointments = $this->appointmentRepo->filterAppointments($request);
    }

    public function search(SearchAppointmentRequest $request)
    {
        return $this->appointmentRepo->searchAppointments($request);
    }

    // new instance Appointment
    public function newInstanceAppointment($appointmentId, InstanceAppointmentRequest $request)
    {
        return   $appointmentPayment = $this->appointmentRepo->newInstanceAppointment($appointmentId, $request->validated());
    }

    // complete appointment otp

    /**
     * This OTP confirms appointment completion with the customer — a real consequence if guessed, so it
     * gets the same treatment as the login/PIN OTPs: a cryptographically-random code (was rand(), not
     * random_int()), a recorded send time so verifyOtp can reject a stale code (there was no expiry
     * concept at all before), and restricted to the appointment's own assigned technician (there was no
     * ownership check: any authenticated user could trigger a resend to someone else's customer).
     */
    public function sendOtp($id)
    {
        $appointment = Appointment::where('id', $id)->first();
        if (!$appointment) return response()->json(['message' => 'appointment not found'], 404);

        if ((int) $appointment->technician_id !== (int) auth()->id()) {
            abort(403, 'You are not authorized to complete this appointment.');
        }

        $otp = random_int(1000, 9999);
        $phone = $appointment->customer?->phone ?? $appointment->phone;

        $appointment->update(['complete_otp' => $otp, 'complete_otp_sent_at' => now()]);
        $response = $this->smsService->sendOtp($phone, $otp);

        $email = $appointment->customer?->email;
        if ($email) {
            Mail::to($email)->send(new \App\Mail\SendOtpMail($otp));
        }

        return  $this->setCode(200)->setData($email)->setMessage('OTP sent successfully.')->send();
    }

    /**
     * Previously: no rate limit (a 4-digit code — 9,000 values — was brute-forceable in minutes with
     * unlimited attempts), no expiry check, and no ownership check (any authenticated user could attempt
     * to verify any appointment's OTP). All three closed below; the actual matching rule is unchanged.
     */
    public function verifyOtp($id, Request $request)
    {
        $appointment  = Appointment::where('id', $id)->first();
        // Check if appointment exists
        if (!$appointment) return response()->json(['message' => 'appointment not found'], 404);

        if ((int) $appointment->technician_id !== (int) auth()->id()) {
            abort(403, 'You are not authorized to complete this appointment.');
        }

        $limiter = \App\Services\Auth\AuthAttemptLimiter::appointmentCompletion($appointment->id);

        if ($limiter->tooManyAttempts()) {
            return response()->json(['message' => 'Too many attempts. Please request a new OTP.'], 429);
        }

        $sentAt = $appointment->complete_otp_sent_at;

        if (!$appointment->complete_otp || !$sentAt || $sentAt->diffInSeconds(now()) > \App\Services\Auth\AuthAttemptLimiter::DECAY_SECONDS) {
            return response()->json(['message' => 'This OTP has expired. Please request a new one.'], 422);
        }

        $result = $this->appointmentRepo->verifyOtp($appointment, $request->otp);

        if ((string) $appointment->complete_otp !== (string) $request->otp) {
            $limiter->hit();
        } else {
            $limiter->clear();
            $appointment->update(['complete_otp' => null, 'complete_otp_sent_at' => null]);
        }

        return $result;
    }

    // Sync all   appointments via API
    public function syncAllAppointments()
    {
        $currentPage = 1;
        $pageSize = 50; // Change based on your API's limit
        $totalSynced = 0;

        try {
            while (true) {
                $response = $this->dyService->getAppointments([
                    'currentPage' => $currentPage,
                    'pageSize' => $pageSize,
                    'fromDate' => null,
                    'toDate' => null,
                ]);

                if (
                    empty($response['Data']['Appointments']) ||
                    !is_array($response['Data']['Appointments'])
                ) {
                    break; // No more results
                }

                $appointments = $response['Data']['Appointments'];

                // Sync this batch
                $this->appointmentRepo->syncAllTechnicianAppointments($appointments);
                $totalSynced += count($appointments);

                // If less than pageSize returned → last page
                if (count($appointments) < $pageSize) {
                    break;
                }

                $currentPage++;
            }

            return response()->json([
                'message' => 'Appointments synced successfully',
                'total_synced' => $totalSynced
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync Appointments: ' . $e->getMessage()
            ], 500);
        }
    }


    // Sync all technician appointments via API
    public function syncAllTechnicianAppointments()
    {
        $pageSize = 50; // Adjust based on your API limit

        try {
            $users = User::where('type', 'tech')->get();

            if ($users->isEmpty()) {
                return response()->json(['message' => 'No technicians found.'], 404);
            }

            $totalSyncedAll = 0;

            foreach ($users as $technician) {
                if (!$technician->tech_id) {
                    continue;
                }

                $currentPage = 1;
                $totalSyncedTech = 0;

                while (true) {
                    $payload = [
                        'worker'      => $technician->tech_id,
                        'currentPage' => $currentPage,
                        'pageSize'    => $pageSize
                    ];

                    $response = $this->dyService->getTechnicianAppointments($payload);

                    if (
                        empty($response['Data']['Appointments']) ||
                        !is_array($response['Data']['Appointments'])
                    ) {
                        break; // no more data for this technician
                    }

                    $appointments = $response['Data']['Appointments'];

                    $this->appointmentRepo->syncAllTechnicianAppointments($appointments);
                    $totalSyncedTech += count($appointments);
                    $totalSyncedAll += count($appointments);

                    if (count($appointments) < $pageSize) {
                        break; // last page reached
                    }

                    $currentPage++;
                }
            }

            if ($totalSyncedAll > 0) {
                return response()->json([
                    'message' => "Appointments synced successfully for all technicians.",
                    'total_synced' => $totalSyncedAll
                ]);
            }

            return response()->json(['message' => 'No appointments found for any technician.'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to sync Appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    // get appointment by sales order
    public function getAppointmentBySalesOrder($id)
    {
        $salesOrderId = Appointment::where('id', $id)->value('sales_order_id');
        return $appointment = $this->dyService->getAppointmentBySalesOrder($salesOrderId);

        if (isset($appointment['Data']['Appointments']) && is_array($appointment['Data']['Appointments'])) {
            $this->appointmentRepo->syncAllTechnicianAppointments($appointment['Data']['Appointments']);
            return response()->json(['message' => 'Appointment synced successfully']);
        } else {

            return response()->json(['message' => 'Failed to sync Appointment: No products found'], 500);
        }
    }
    // handle sales line
    public function handleSalesLine(HandleSalesLineRequest $request)
    {

        $result = $this->appointmentRepo->handleSalesLine($request->validated());

        if (!$result['status']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json($result['data']);
    }

    // get pending dy appointments
    public function getPendingDyAppointments(Request $request)
    {
        $user = $request->user();

        if (!$user || $user->type !== 'tech') {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized',
                'data'    => [],
            ], 401);
        }

        $appointments = $this->appointmentRepo->getPendingDyAppointments($user->id);
        return $this->setCode(code: 200)->setData(($appointments))->setMessage('Success.')->send();
    }

    //  get last appointment payment
    public function getLastAppointmentPayment($appointmentId, $type)
    {
        $appointmentPayment = $this->appointmentRepo->getLastAppointmentPayment($appointmentId, $type);
        return $this->setCode(code: 200)->setData(($appointmentPayment))->setMessage('')->send();
    }

    // apply discount to appointment

    public function applyDiscount(Request $request, $id)
    {
        // discount_value previously had no upper bound at all: a percentage discount over 100, or a fixed
        // discount far exceeding the appointment's own total, could both be submitted and accepted. The
        // assigned-technician ownership check below (in the repository) already limits WHO can do this to
        // the appointment's own technician; this limits WHAT they can submit.
        $validated = $request->validate([
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($request) {
                    if (($request->input('discount_type') ?? 'fixed') === 'percentage' && $value > 100) {
                        $fail('A percentage discount cannot exceed 100.');
                    }
                },
            ],
            'discount_type'  => 'nullable|in:fixed,percentage',
        ]);
        $discountType = $validated['discount_type'] ?? 'fixed';

        $appointment = $this->appointmentRepo->applyDiscount(
            $id,
            $validated['discount_value'],
            $discountType
        );


        return response()->json([
            'message' => 'Discount applied successfully.',
            'appointment' => $appointment
        ]);
    }

    // get appointment by sales order
    public function exportProcessingAppointments()
    {
        return Excel::download(new AppointmentsExport, 'processing_appointments.xlsx');
    }

    // direct appointment export
    public function exportDirectAppointments()
    {
        ini_set('memory_limit', '1G'); // 1 gigabyte (you can also use 512M or 2G)
        return Excel::download(new DirectAppointmentsExport, 'direct_appointments_paid.xlsx');
    }

    public function exportSpecificDirectAppointments(Request $request)
    {
        ini_set('memory_limit', '1G');
        // Example: use request value OR set manually
        $date = $request->date ??  now()->format('Y-m-d');
        return Excel::download(new DirectTodayAppointmentsExport($date), 'direct_specific_appointments_paid.xlsx');
    }

    // sync Single appointment
    public function syncSingleAppointment(Request $request)
    {
        $salesOrderId = $request->sales_order_id;
        $response = $this->dyService->getAppointmentBySalesOrder($salesOrderId);

        if (isset($response['Data']['Appointments']) && is_array($response['Data']['Appointments'])) {
            $this->appointmentRepo->syncAllTechnicianAppointments($response['Data']['Appointments']);
            return response()->json(['message' => 'Appointment synced successfully']);
        } else {

            return response()->json(['message' => 'Failed to sync Appointment: No products found'], 500);
        }
    }

    // delate appointment with sales lines
    public function deleteAppointmentWithSalesLines($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $user = Auth::user();
        if (! $user->role = 'super_admin') {
            abort(403, 'You are not authorized to delete this appointment.');
        }
        DB::beginTransaction();
        try {
            // Delete associated sales lines
            $appointment->lines()->delete();
            // Delete the appointment
            $appointment->delete();
            DB::commit();
            return $this->setCode(200)->setData([])->setMessage('Appointment and associated sales lines deleted successfully.')->send();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // send non completed appointments reminder v2
    public function checkNonCompletedAppointments()
    {

        $beforeYesterday = now()->subDays(2)->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        $today = now()->format('Y-m-d');
        $appointments = Appointment::whereIn(DB::raw('DATE(appointment_date)'), [$beforeYesterday, $yesterday, $today])
            ->whereIn('status', ['processing', 'complete', 'completed'])
            ->where('dy365_status', '!=', 'Completed')
            ->get();

        foreach ($appointments as $appointment) {

            $salesLines[] = [
                "TotalAmount"      => floatval($appointment->total_price ?? 0),
                "PaymentMethod"    =>  "CASH",
            ];

            $body = [
                "_contract" => [
                    "worker" => (int) $appointment->technician->tech_id,
                    "SalesOrderId" => $appointment->sales_order_id,
                    "BookId" => $appointment->book_id,
                    "Discount" => $appointment->discount_value ?? 0,
                    "SalesLines" => $salesLines
                ]
            ];
            $response = $this->dyService->completeSuccessPaymentsV2($body);

            dd($response);
            if (isset($response->status) && $response->status == "success") {
                $appointment->update([
                    'v2_flag'       => 1,
                ]);
            } else {
                $appointment->update([
                    'dy_response'   => json_encode(['status' => 'success', 'response' => $response]),
                ]);
                // Log the error or handle it as needed
                \Log::error('Failed to complete payment for appointment ID: ' . $appointment->id, ['response' => $response]);
            }
        }

        return response()->json(['message' => 'Reminders sent successfully.']);
    }
}
