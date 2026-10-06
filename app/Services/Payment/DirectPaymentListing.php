<?php

namespace App\Services\Payment;

use App\Models\DirectAppointmentPayment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * The paginated list of direct payments made through a gateway (Tabby, Tamara, ClickPay), newest first.
 *
 * Every filter combines with AND. Identifiers are matched exactly (they are references, not search text), and all values
 * are bound parameters. Dates filter on created_at, inclusive, in the application timezone.
 *
 *   type          tabby | tamara | clickpay        (none = all three gateways; CASH / POS / TRNS are never included)
 *   reference_id  exact
 *   payment_id    exact
 *   search        exact, matches reference_id OR payment_id
 *   date          one day
 *   from_date / to_date   inclusive range
 */
final class DirectPaymentListing
{
    public const DEFAULT_PER_PAGE = 10;

    public const MAX_PER_PAGE = 100;

    /** @param array<string, mixed> $filters  already validated (see DirectPaymentIndexRequest) */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = DirectAppointmentPayment::query()
            ->select(['id', 'direct_appointment_id', 'sales_order_id', 'book_id', 'price', 'status', 'payment_type', 'phone', 'reference_id', 'payment_id', 'created_at', 'updated_at'])
            ->whereIn('payment_type', DirectPaymentGateways::storedLabels($filters['type'] ?? null));

        if (filled($filters['reference_id'] ?? null)) {
            $query->where('reference_id', $filters['reference_id']);
        }

        if (filled($filters['payment_id'] ?? null)) {
            $query->where('payment_id', $filters['payment_id']);
        }

        if (filled($filters['search'] ?? null)) {
            $search = $filters['search'];

            $query->where(fn ($q) => $q->where('reference_id', $search)->orWhere('payment_id', $search));
        }

        [$from, $to] = $this->dateRange($filters);

        if ($from !== null) {
            $query->where('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('created_at', '<=', $to);
        }

        $perPage = min(max((int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE), 1), self::MAX_PER_PAGE);
        $page    = max((int) ($filters['currentPage'] ?? 1), 1);

        return $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    private function dateRange(array $filters): array
    {
        $day = $filters['date'] ?? null;
        $from = $day ?: ($filters['from_date'] ?? null);
        $to   = $day ?: ($filters['to_date'] ?? null);

        $parse = fn (string $date) => Carbon::createFromFormat('Y-m-d', $date, (string) config('app.timezone'));

        return [
            filled($from) ? $parse($from)->startOfDay() : null,
            filled($to) ? $parse($to)->endOfDay() : null,
        ];
    }
}
