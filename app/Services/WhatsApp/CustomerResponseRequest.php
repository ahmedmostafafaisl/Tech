<?php

namespace App\Services\WhatsApp;

use App\Services\DY365\DyTransportOutcome;

/**
 * Builds the Dynamics change request for a customer's WhatsApp button reply and
 * turns Dynamics' answer into the flag / dy_response stored on the
 * PreAppointmentMessage — the same meaning the manual resend command
 * (dy:send-customer-responses) has always used: flag = 1 only when DY answered
 * with Status = true.
 */
final class CustomerResponseRequest
{
    private const REQUEST_TYPE = ['confirm' => 2, 'cancel' => 0, 'reschedule' => 1];

    /** @throws \InvalidArgumentException for 'pending' or anything unknown */
    public static function body(?string $bookId, ?string $salesOrderId, string $customerResponse): array
    {
        if (! array_key_exists($customerResponse, self::REQUEST_TYPE)) {
            throw new \InvalidArgumentException("Unknown customer_response: {$customerResponse}");
        }

        return [
            '_contract' => [
                'bookId'       => $bookId,
                'salesOrderId' => $salesOrderId,
                'actionOwner'  => 2,
                'requestType'  => self::REQUEST_TYPE[$customerResponse],
            ],
        ];
    }

    /**
     * @param  array{outcome:string,status:?int,body:?array,error:?string}  $outcome  from DyService::submitCustomerChangeRequestWithin()
     * @return array{flag:int,dy_response:array}
     */
    public static function record(array $outcome): array
    {
        if ($outcome['outcome'] === DyTransportOutcome::ANSWERED) {
            $status = (int) $outcome['status'];
            $httpOk = $status >= 200 && $status < 300;
            $body   = $outcome['body'];

            $response = ['outcome' => 'answered', 'ok' => $httpOk, 'status' => $status];

            if ($httpOk) {
                $response['data'] = $body;
            } else {
                $response['error'] = $body ?? ['message' => "Dynamics replied with HTTP {$status}"];
            }

            return [
                'flag'        => ($httpOk && ($body['Status'] ?? null) === true) ? 1 : 0,
                'dy_response' => $response,
            ];
        }

        // Unconfirmed: DY may still have processed it — flag stays 0 so it is visibly
        // unresolved, and 'outcome' tells whoever resends it that it might be a duplicate.
        // Not sent: DY certainly never got it, so resending is safe.
        return [
            'flag'        => 0,
            'dy_response' => [
                'outcome' => $outcome['outcome'],
                'ok'      => false,
                'status'  => $outcome['status'],
                'error'   => ['message' => $outcome['error']],
            ],
        ];
    }
}
