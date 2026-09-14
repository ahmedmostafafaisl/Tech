<?php

namespace App\Http\Controllers\Api\CompleteForm;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitCompleteForm\SubmitAppointmentFormRequest;
use App\Http\Resources\SubmitCompleteForm\AppointmentFormSubmissionResource;
use App\Models\DirectAppointment;
use App\Repositories\Interfaces\AppointmentFormSubmissionRepositoryInterface;
use App\Services\Payment\PaymentCompletionDispatcher;
use App\Services\SubmitCompleteForm\AppointmentFormSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentFormSubmissionController extends Controller
{
    public function __construct(
        private readonly AppointmentFormSubmissionService $service,
        private readonly AppointmentFormSubmissionRepositoryInterface $repo,
        private readonly PaymentCompletionDispatcher $dispatcher,
    ) {}

    public function submit(SubmitAppointmentFormRequest $request): JsonResponse
    {
        $validated    = $request->validated();
        $salesOrderId = $validated['sales_order_id'];
        $bookId       = $validated['book_id'];

        $controller = app(\App\Http\Controllers\Api\NewDirectIntegrationController::class);
        // 0) check stock for all items before proceeding
        $stockCheck = $controller->salesLinesSummaryByBookId($bookId);
        if ($stockCheck !== null) {
            return $stockCheck; // returns the 400 error response
        }

        // ── Pre-check: verify appointment is eligible before doing anything ──
        $appointment = DirectAppointment::where('sales_order_id', $salesOrderId)
            ->where('book_id', $bookId)
            ->orderByDesc('id')
            ->first();

        if (!$appointment) {
            return response()->json([
                'message' => 'No appointment found for this sales order and book.',
                'errors'  => [
                    'sales_order_id' => ['No appointment found for this sales order and book.'],
                    'book_id'        => ['No appointment found for this sales order and book.'],
                ],
            ], 422);
        }

        $check = $this->dispatcher->preCheck($appointment);

        if (!($check['ok'] ?? false)) {
            Log::warning('Form submission blocked by pre-check', [
                'appointment_id' => $appointment->id,
                'sales_order_id' => $salesOrderId,
                'book_id'        => $bookId,
                'reason'         => $check['reason'] ?? 'unknown',
                'check'          => $check,
            ]);

            return response()->json([
                'message' => 'Appointment is not eligible for submission.',
                'reason'  => $check['reason'] ?? 'unknown',
            ], 422);
        }

        // ── Proceed with submission ───────────────────────────────────────────
        $submissionId = $this->service->submit(
            $validated,
            $request->allFiles(),
            optional($request->user())->id
        );

        // Background Dynamics dispatch + payment completion are handled
        // inside the service via DB::afterCommit → artisan command.

        $submission = $this->repo->loadForResponse($submissionId);

        return (new AppointmentFormSubmissionResource($submission))
            ->additional(['message' => 'Form submitted successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function getBySalesOrderOrBook(Request $request): JsonResponse
    {
        $salesOrderId = $request->query('salesOrderId');
        $bookId       = $request->query('bookId');

        $submission = $this->repo->loadForSalesOrderOrBook($salesOrderId, $bookId);

        if (!$submission) {
            return response()->json([
                'message' => 'No submission found for the given sales order or book.',
            ], 404);
        }

        return (new AppointmentFormSubmissionResource($submission))
            ->additional(['message' => 'Submission retrieved successfully.'])
            ->response()
            ->setStatusCode(200);
    }
}
