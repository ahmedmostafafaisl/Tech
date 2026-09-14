<?php

namespace App\Repositories\Interfaces;

use App\Models\AppointmentFormSubmission;

interface AppointmentFormSubmissionRepositoryInterface
{
    public function createSubmission(array $data): AppointmentFormSubmission;

    public function upsertValues(int $submissionId, array $rows): void;

    public function loadForResponse(int $submissionId): AppointmentFormSubmission;
    public function loadForDynamics(int $submissionId);
    // get submission with values and fields for specific sales order or book id
    public function loadForSalesOrderOrBook(string $salesOrderId = null, string $bookId = null);
}
