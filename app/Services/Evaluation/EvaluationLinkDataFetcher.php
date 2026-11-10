<?php

namespace App\Services\Evaluation;

use App\Models\User;
use App\Services\DY365\DyService;

/**
 * The four fields EvaluationLinkResolver::buildWithEncryptedData() encodes into an evaluation link
 * (OrderTypeId, book_id, tech_user_name, district), fetched the same way refSingleAppointmentByBookId()
 * does — a live DY365 call for OrderTypeId/District/Worker, then a local User lookup by Worker for the
 * tech's username — but without that method's much heavier sales-lines/stock-map work, which the invoice
 * send flows (SendInvoiceLinkJob, InvoiceWhatsAppSenderService) have no use for.
 *
 * Also returns customer_phone (DY365's CustomerPhoneNumber) — not one of the four encoded into the link
 * itself, but DY365's own, authoritative number for who to actually send the evaluation to, preferred over
 * whatever phone a caller already has on hand (e.g. the local DirectAppointment.customer_phone).
 */
class EvaluationLinkDataFetcher
{
    public function __construct(private readonly DyService $dyService) {}

    /**
     * @return array{OrderTypeId: ?string, book_id: string, tech_user_name: ?string, district: ?string, customer_phone: ?string}
     */
    public function fetchForBookId(string $bookId): array
    {
        $response = $this->dyService->getAppointmentByBookIdNew($bookId, 0);
        $data = (is_array($response) && ($response['Status'] ?? false) === true) ? ($response['Data'] ?? []) : [];

        $worker = $data['Worker'] ?? null;
        $techUserName = $worker ? User::where('tech_id', $worker)->orderByDesc('id')->value('username') : null;

        return [
            'OrderTypeId'    => $data['OrderTypeId'] ?? null,
            'book_id'        => $bookId,
            'tech_user_name' => $techUserName,
            'district'       => $data['District'] ?? null,
            'customer_phone' => $data['CustomerPhoneNumber'] ?? null,
        ];
    }
}
