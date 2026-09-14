<?php

namespace App\Services\Dynamics;

use App\Models\CompleteForm;
use App\Models\DirectAppointment;

class DynamicsAttachmentPayloadService
{
    public function buildPayload(string $bookId, ?string $note, array $attachments, ?string $serviceNote = null): array
    {
        return [
            '_contract' => [
                'BookId' => $bookId,
                'TechServiceNote' => $serviceNote,
                'Note' => $note,
                'AttachmnetsURLs' => array_values($attachments),
            ],
        ];
    }

    public function queueAppointmentPayload(
        DirectAppointment $appointment,
        ?string $note,
        array $attachments,
        bool $resetResponse = false,
        ?string $serviceNote = null
    ): array {
        $payload = $this->buildPayload(
            bookId: $appointment->book_id,
            note: $note,
            attachments: $attachments,
            serviceNote: $serviceNote
        );

        $updateData = [
            'dy_attachment_body' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'dy_attachment_status' => 'queued',
        ];

        if ($note !== null) {
            $updateData['notes'] = $note;
        }

        if ($resetResponse) {
            $updateData['dy_attachment_response'] = null;
        }

        $appointment->update($updateData);

        $cmd = 'php ' . escapeshellarg(base_path('artisan')) .
            ' appointments:send-attachments ' . escapeshellarg($appointment->id) .
            ' > /dev/null 2>&1 &';

        exec($cmd);

        return $payload;
    }

    public function makeAttachment(?string $url, string $description): ?array
    {
        if (empty($url)) {
            return null;
        }

        return [
            'URL' => $url,
            'Description' => $description,
        ];
    }

    public function rebuildAndQueueFromCompleteForm(DirectAppointment $appointment): array
    {
        $completeForm = CompleteForm::query()
            ->where('book_id', $appointment->book_id)
            ->latest('id')
            ->first();

        if (!$completeForm) {
            throw new \RuntimeException('No complete form found for this appointment');
        }

        $attachments = array_values(array_filter([
            $this->makeAttachment($completeForm->home_salt_image, 'home_salt_image'),
            $this->makeAttachment($completeForm->device_salt_image, 'device_salt_image'),
            $this->makeAttachment($completeForm->carbon_depletion_image, 'carbon_depletion_image'),
            $this->makeAttachment($completeForm->sink_cleaning_image, 'sink_cleaning_image'),
            $this->makeAttachment($completeForm->drain_connection_image, 'drain_connection_image'),
            $this->makeAttachment($completeForm->additional_image, 'additional_image'),
        ]));

        $note = $completeForm->notes
            ?? $completeForm->service_name
            ?? $completeForm->solution
            ?? null;

        return $this->queueAppointmentPayload(
            appointment: $appointment,
            note: $note,
            attachments: $attachments,
            resetResponse: true,
        );
    }

    public function resendByAppointment(DirectAppointment $appointment): array
    {
        if (!empty($appointment->dy_attachment_body)) {
            $appointment->update([
                'dy_attachment_status' => 'queued',
                'dy_attachment_response' => null,
            ]);

            $cmd = 'php ' . escapeshellarg(base_path('artisan')) .
                ' appointments:send-attachments ' . escapeshellarg($appointment->id) .
                ' > /dev/null 2>&1 &';

            exec($cmd);

            return json_decode($appointment->dy_attachment_body, true) ?? [];
        }

        return $this->rebuildAndQueueFromCompleteForm($appointment);
    }
}
