<?php

namespace App\Repositories\SubmitCompleteForm;

use App\Models\AppointmentFormSubmission;
use App\Models\AppointmentFormSubmissionValue;
use App\Repositories\Interfaces\AppointmentFormSubmissionRepositoryInterface;

class AppointmentFormSubmissionRepository implements AppointmentFormSubmissionRepositoryInterface
{
    public function createSubmission(array $data): AppointmentFormSubmission
    {
        return AppointmentFormSubmission::create($data);
    }

    public function upsertValues(int $submissionId, array $rows): void
    {
        // $rows: each row has field_id + value_* columns
        foreach ($rows as $row) {
            AppointmentFormSubmissionValue::updateOrCreate(
                ['submission_id' => $submissionId, 'field_id' => $row['field_id']],
                $row
            );
        }
    }

    public function loadForResponse(int $submissionId): AppointmentFormSubmission
    {
        return AppointmentFormSubmission::query()
            ->with([
                'type:id,code,name_ar,name_en',
                'rootOption:id,appointment_type_id,parent_id,label_ar,label_en,sort_order,is_active',
                'problemOption:id,appointment_type_id,parent_id,label_ar,label_en,sort_order,is_active',
                'solutionOption:id,appointment_type_id,parent_id,label_ar,label_en,sort_order,is_active',
                'values.field:id,field_key,label_ar,label_en,field_type',
            ])
            ->findOrFail($submissionId);
    }

    public function loadForDynamics(int $submissionId)
    {
        return AppointmentFormSubmission::query()
            ->with(['values.field:id,field_key,label_ar,label_en,field_type'])
            ->findOrFail($submissionId);
    }

    // get submission with values and fields for specific sales order or book id
    public function loadForSalesOrderOrBook(string $salesOrderId = null, string $bookId = null)
    {
        $query = AppointmentFormSubmission::query()
            ->with(['values']);

        if ($salesOrderId) {
            $query->where('sales_order_id', $salesOrderId);
        }

        if ($bookId) {
            $query->where('book_id', $bookId);
        }

        return $query->firstOrFail();
    }
}
