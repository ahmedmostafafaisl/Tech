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

        // Time box. The Dynamics send itself is already a background command
        // (DB::afterCommit in the service), so what made this endpoint time out was
        // everything BEFORE it: the stock check and preCheck make several DY calls
        // with 60s timeouts and retries. They may now use at most 40s of the ~60s
        // this request is allowed; the rest is left for saving + uploads.
        \App\Services\DY365\DyRequestBudget::begin(40);
        $prechecksSkipped = false;

        try {
            $controller = app(\App\Http\Controllers\Api\NewDirectIntegrationController::class);
            // 0) check stock for all items before proceeding
            $stockCheck = $controller->salesLinesSummaryByBookId($bookId);
            if ($stockCheck !== null) {
                if (\App\Services\DY365\DyRequestBudget::exhausted()) {
                    // The result was computed after DY ran out of time (missing stock
                    // lookups look like "no stock"), so it can't be trusted. The
                    // background send re-validates stock before anything reaches DY.
                    $prechecksSkipped = true;
                    Log::warning('Form submission: stock check skipped — Dynamics too slow', ['book_id' => $bookId]);
                } else {
                    return $stockCheck; // returns the 400/503 error response
                }
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
                if (\App\Services\DY365\DyRequestBudget::exhausted()) {
                    // Same reasoning: a failure that happened because DY timed out is
                    // not a verdict on the appointment. dispatch() runs the same checks.
                    $prechecksSkipped = true;
                    Log::warning('Form submission: pre-check skipped — Dynamics too slow', [
                        'appointment_id' => $appointment->id,
                        'book_id'        => $bookId,
                        'reason'         => $check['reason'] ?? 'unknown',
                    ]);
                } else {
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
            }

            // preCheck can also fail OPEN when DY times out (it falls back to local
            // data and passes), so record a spent budget even if nothing was rejected.
            if (\App\Services\DY365\DyRequestBudget::exhausted()) {
                $prechecksSkipped = true;
            }
        } finally {
            \App\Services\DY365\DyRequestBudget::clear();
        }

        // ── Proceed with submission ───────────────────────────────────────────
        $submissionId = $this->service->submit(
            $validated,
            $request->allFiles(),
            optional($request->user())->id
        );

        // Background Dynamics dispatch + payment completion are handled
        // inside the service via DB::afterCommit → artisan command, so the
        // request ends here instead of waiting on Dynamics.
        $submission = $this->repo->loadForResponse($submissionId);

        return (new AppointmentFormSubmissionResource($submission))
            ->additional([
                'message'           => 'Form submitted successfully.',
                'sent_to_dy'        => true,   // the background send has been launched
                'dy_confirmed'      => false,  // Dynamics' answer arrives later, in the background
                'prechecks_skipped' => $prechecksSkipped,
            ])
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
