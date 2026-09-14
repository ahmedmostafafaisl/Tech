<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

class PaymentCompletionValidator
{
    public static function validate(array $body): array
    {
        $contract = $body['_contract'] ?? null;

        if (!$contract) {
            return self::fail('missing_contract');
        }

        $bookId = $contract['BookId'] ?? null;
        if (!$bookId) {
            return self::fail('missing_book_id');
        }

        // Accept SalesLines OR salesLines
        $salesLines =
            $contract['SalesLines']
            ?? $contract['salesLines']
            ?? null;

        if (!is_array($salesLines) || empty($salesLines)) {
            return self::fail('sales_lines_empty');
        }

        return [
            'ok'         => true,
            'book_id'    => $bookId,
            'salesLines' => $salesLines,
        ];
    }

    protected static function fail(string $reason): array
    {
        Log::warning('PaymentCompletionValidator failed', [
            'reason' => $reason,
        ]);

        return [
            'ok'     => false,
            'reason' => $reason,
        ];
    }
}
