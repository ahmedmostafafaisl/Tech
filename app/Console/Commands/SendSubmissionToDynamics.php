<?php

namespace App\Console\Commands;

use App\Services\SubmitCompleteForm\AppointmentFormSubmissionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubmissionToDynamics extends Command
{
    /**
     * appointments:send-submission-dynamics {submission_id}
     *
     * Called in the background by AppointmentFormSubmissionController
     * after a successful form submission. Responsible for:
     *   1. Loading the submission + its related data
     *   2. Verifying the linked appointment exists and is paid
     *   3. Collecting notes + file attachment URLs
     *   4. Sending everything to Dynamics 365 via the payload service
     */
    protected $signature = 'appointments:send-submission-dynamics
                            {submission_id : The AppointmentFormSubmission ID to dispatch}';

    protected $description = 'Send a form submission\'s notes and attachments to Dynamics 365 (runs in background)';

    public function __construct(
        private readonly AppointmentFormSubmissionService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $submissionId = (int) $this->argument('submission_id');

        // ── Step 1: Basic sanity check ──────────────────────────────────
        if ($submissionId <= 0) {
            $this->error("Invalid submission_id provided: {$submissionId}");
            Log::error('SendSubmissionToDynamics: invalid submission_id', [
                'submission_id' => $submissionId,
            ]);
            return self::FAILURE;
        }

        $this->info("Starting Dynamics dispatch for submission #{$submissionId}");

        // ── Step 2: Delegate to service ─────────────────────────────────
        // All the real logic (DB loads, appointment check, notes/attachments
        // collection, queueing the payload) lives in the service method,
        // which already has its own try/catch + Log::error internally.
        try {
            $this->service->sendSubmissionToDynamics($submissionId);

            $this->info("Submission #{$submissionId} dispatched to Dynamics successfully.");

            Log::info('SendSubmissionToDynamics command completed', [
                'submission_id' => $submissionId,
            ]);

            return self::SUCCESS;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Submission row was deleted or never committed — shouldn't happen
            // in normal flow, but guard anyway.
            $this->error("Submission #{$submissionId} not found in database.");
            Log::error('SendSubmissionToDynamics: submission not found', [
                'submission_id' => $submissionId,
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Unexpected error: {$e->getMessage()}");
            Log::error('SendSubmissionToDynamics command failed', [
                'submission_id' => $submissionId,
                'error'         => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
            ]);
            return self::FAILURE;
        }
    }
}
