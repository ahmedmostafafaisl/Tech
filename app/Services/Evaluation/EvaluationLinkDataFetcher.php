<?php

namespace App\Services\Evaluation;

use App\Models\DirectAppointment;
use App\Models\User;
use App\Services\DY365\DyService;

/**
 * The five fields EvaluationLinkResolver::buildWithEncryptedData() encodes into an evaluation link
 * (OrderTypeId, book_id, tech_user_name, district, complete_v2_calling), fetched the same way
 * refSingleAppointmentByBookId() does — a live DY365 call for OrderTypeId/District/Worker, then a local User
 * lookup by Worker for the tech's username — but without that method's much heavier sales-lines/stock-map
 * work, which the invoice send flows (SendInvoiceLinkJob, InvoiceWhatsAppSenderService) have no use for.
 *
 * Also returns customer_phone (DY365's CustomerPhoneNumber) — not one of the five encoded into the link
 * itself, but DY365's own, authoritative number for who to actually send the evaluation to, preferred over
 * whatever phone a caller already has on hand (e.g. the local DirectAppointment.customer_phone).
 */
class EvaluationLinkDataFetcher
{
    public function __construct(private readonly DyService $dyService) {}

    /**
     * complete_v2_calling here is a plain date/time string (e.g. "2026-11-10 14:30:00") — see
     * extractCompletionDateTime() for how the local marker's "{state}:" prefix is stripped from it.
     *
     * @return array{OrderTypeId: ?string, book_id: string, tech_user_name: ?string, district: ?string, customer_phone: ?string, complete_v2_calling: string}
     */
    public function fetchForBookId(string $bookId): array
    {
        $response = $this->dyService->getAppointmentByBookIdNew($bookId, 0);
        $data = (is_array($response) && ($response['Status'] ?? false) === true) ? ($response['Data'] ?? []) : [];

        $worker = $data['Worker'] ?? null;
        $techUserName = $worker ? User::where('tech_id', $worker)->orderByDesc('id')->value('username') : null;

        $completeV2Calling = DirectAppointment::where('book_id', $bookId)
            ->latest('id')
            ->value('complete_v2_calling');

        return [
            'OrderTypeId'         => $data['OrderTypeId'] ?? null,
            'book_id'             => $bookId,
            'tech_user_name'      => $techUserName,
            'district'            => $data['District'] ?? null,
            'customer_phone'      => $data['CustomerPhoneNumber'] ?? null,
            'complete_date_time' => $this->extractCompletionDateTime($completeV2Calling),
        ];
    }

    /**
     * The local DirectAppointment's own completion marker (set by CompleteSuccessPaymentsCommand) is a
     * "{state}:{datetime}" string, e.g. "done:2026-11-10 14:30:00" — this returns just the date/time
     * portion, not the state prefix, matching the same extraction InvoiceController already does for this
     * same column. now() (current date/time) when the marker is empty (not completed/marked yet, or no
     * local row exists at all for this book_id), or when the date portion itself fails to parse (a
     * malformed value from DY365) — a bad or missing value here never blocks building the link over it.
     */
    private function extractCompletionDateTime(?string $marker): string
    {
        if (empty($marker)) {
            return now()->toDateTimeString();
        }

        // Split on the first ':' only — the state prefix itself never contains one, but the time portion
        // that follows does (its own HH:MM:SS), so a plain explode(':', ...) without a limit would cut it
        // apart incorrectly.
        $dateStr = str_contains($marker, ':')
            ? trim(explode(':', $marker, 2)[1] ?? '')
            : trim($marker);

        try {
            return \Carbon\Carbon::parse($dateStr)->toDateTimeString();
        } catch (\Throwable $e) {
            return now()->toDateTimeString();
        }
    }
}
